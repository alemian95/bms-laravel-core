# 01 — Preparazione VPS e installazione Coolify

Guida per partire da una VPS "nuda" (Ubuntu 24.04 LTS consigliato) e
arrivare ad avere Coolify pronto con Docker funzionante e SSH messo in
sicurezza. Nessuna conoscenza sistemistica avanzata richiesta:
copia/incolla i comandi nell'ordine indicato.

---

## 1. Scelta VPS

**Specifiche minime consigliate** per testare bms-core + Coolify:

| Risorsa | Minimo di test | Consigliato produzione piccola |
| ------- | -------------- | ------------------------------ |
| CPU     | 2 vCPU         | 2–4 vCPU                       |
| RAM     | 4 GB           | 6–8 GB                         |
| Disco   | 40 GB SSD      | 80 GB SSD                      |
| OS      | Ubuntu 24.04   | Ubuntu 24.04                   |

> Coolify da solo consuma ~500 MB di RAM. Con 2 GB si rischia OOM
> durante i build (il build frontend Vite è la fase più pesante).

**Provider economici testati con Coolify:** Hetzner (CX22, ~€4/mese),
Contabo, Netcup, DigitalOcean, Vultr. Per test iniziali Hetzner è il
miglior rapporto qualità/prezzo in Europa.

Appunta al momento del provisioning:

- IP pubblico della VPS
- Credenziali SSH iniziali (password root o chiave)

---

## 2. Primo accesso e aggiornamento sistema

Dal tuo computer:

```bash
ssh root@IP_VPS
```

Una volta dentro:

```bash
apt update && apt upgrade -y
apt install -y curl wget git ufw fail2ban unattended-upgrades
```

`unattended-upgrades` installa automaticamente le patch di sicurezza
del sistema operativo — abilitiamolo:

```bash
dpkg-reconfigure -plow unattended-upgrades
```

Conferma con "Yes" alla domanda.

---

## 3. Utente non-root e hardening SSH

Lavorare come `root` è rischioso. Crea un utente con accesso sudo:

```bash
adduser deploy           # segui il prompt, imposta password forte
usermod -aG sudo deploy
```

### 3.1 Imposta l'accesso SSH tramite chiave

Dal **tuo computer** (non dalla VPS), se non hai già una chiave SSH:

```bash
ssh-keygen -t ed25519 -C "tuo@nome"
```

Copia la chiave pubblica sulla VPS:

```bash
ssh-copy-id deploy@IP_VPS
```

Verifica che l'accesso funzioni:

```bash
ssh deploy@IP_VPS
```

### 3.2 Disabilita login password e login root

Sulla VPS (come `deploy`):

```bash
sudo nano /etc/ssh/sshd_config.d/99-hardening.conf
```

Contenuto:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

Salva e ricarica:

```bash
sudo systemctl reload ssh
```

**Lascia aperta la sessione attuale** e prova il login da un'altra
finestra del terminale prima di chiuderla, per evitare di lockarti
fuori. Se il login con chiave funziona, puoi chiudere la sessione
originale.

---

## 4. Firewall (UFW)

Apri solo ciò che serve: SSH (22), HTTP (80), HTTPS (443), dashboard
Coolify (8000 — la chiuderemo dopo aver configurato un dominio).

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8000/tcp
sudo ufw --force enable
sudo ufw status
```

---

## 5. Installazione Docker

Coolify installa Docker da sé, ma preferisco installarlo prima dal
repo ufficiale (versione più fresca, meno sorprese).

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker deploy
```

**Ri-effettua il login SSH** per applicare l'appartenenza al gruppo
`docker`:

```bash
exit
ssh deploy@IP_VPS
docker run --rm hello-world     # deve stampare "Hello from Docker!"
```

---

## 6. Installazione Coolify

Installazione "one-liner" ufficiale:

```bash
curl -fsSL https://cdn.coollabs.io/coolify/install.sh | sudo bash
```

L'installer:

- scarica e avvia lo stack Coolify (container Docker)
- crea la cartella `/data/coolify` con config e dati persistenti
- apre la dashboard sulla porta **8000**

Durante l'installazione stampa a video una URL e un token iniziale.
Appuntali: ti servono al primo accesso.

A fine installazione apri nel browser:

```
http://IP_VPS:8000
```

Alla prima schermata Coolify chiede di creare l'account admin (email
+ password forte). Crealo: sarà l'account di amministrazione del
pannello.

---

## 7. Dominio + TLS per Coolify (opzionale ma consigliato)

Per accedere in HTTPS al pannello (e chiudere la porta 8000 al mondo),
punta un sottodominio al tuo IP — ad esempio `coolify.tuodominio.it` —
e in Coolify:

1. **Settings → Instance → Fully Qualified Domain Name**: inserisci
   `https://coolify.tuodominio.it`
2. Salva. Coolify richiede il certificato a Let's Encrypt
   automaticamente (tramite Traefik interno).

Quando la dashboard risponde su HTTPS, puoi chiudere la 8000:

```bash
sudo ufw delete allow 8000/tcp
sudo ufw reload
```

---

## 8. Sanity check finale

```bash
docker ps                                   # vedi i container di Coolify up
sudo ufw status                             # 22, 80, 443 aperte
ssh -V                                      # OpenSSH presente
sudo unattended-upgrade --dry-run -d | tail # verifica upgrades auto OK
```

La VPS è pronta. Vai a [`02-setup-coolify-app.md`](02-setup-coolify-app.md)
per creare l'applicazione bms-core.
