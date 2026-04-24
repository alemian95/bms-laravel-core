# 01b — Configurazione DNS (registrar / Cloudflare / Coolify)

Guida alla parte di rete che sta **fra** l'installazione della VPS
([`01-setup-vps.md`](01-setup-vps.md)) e la creazione dell'applicazione
in Coolify ([`02-setup-coolify-app.md`](02-setup-coolify-app.md)): come
far risolvere il dominio sull'IP della VPS e come far sì che Coolify
ottenga automaticamente il certificato TLS.

> Serve **prima** di collegare l'app a Coolify: senza DNS corretti
> Let's Encrypt fallisce la validazione e il deploy resta in HTTP /
> 502.

---

## 1. Quadro generale

Tre livelli interagiscono. Ognuno ha un suo scope:

| Livello             | Dove si configura               | Che cosa controlla                        |
| ------------------- | ------------------------------- | ----------------------------------------- |
| **Registrar**       | pannello del provider che ti ha venduto il dominio (Aruba, Register, Netsons, OVH, GoDaddy, Namecheap…) | i **nameserver** autoritativi del dominio |
| **Zona DNS**        | nameserver autoritativi (registrar stesso **oppure** Cloudflare, DNSimple, Route 53…) | i **record** (A, AAAA, CNAME, MX, TXT, CAA) |
| **VPS + Coolify**   | firewall UFW + Coolify UI       | ricezione traffico HTTP/HTTPS + emissione certificato |

Strategia consigliata per bms-core: **registrar → nameserver Cloudflare
→ Coolify (Let's Encrypt HTTP-01)**. Cloudflare è gratuito, offre un
pannello DNS più veloce e funzionalità aggiuntive (proxy, WAF, cache).
Chi preferisce restare sul registrar trova la variante nella §6.

---

## 2. Record DNS minimi da creare

Per una tipica installazione bms-core servono almeno due sottodomini:

| Nome                       | Tipo | Valore           | Scopo                         |
| -------------------------- | ---- | ---------------- | ----------------------------- |
| `bms.tuodominio.it`        | A    | IP pubblico VPS  | applicazione bms-core         |
| `coolify.tuodominio.it`    | A    | IP pubblico VPS  | pannello Coolify in HTTPS     |

Se la VPS ha IPv6 (es. Hetzner lo fornisce di default), aggiungi anche
un record **AAAA** con lo stesso nome e l'indirizzo IPv6. Non è
obbligatorio, ma evita errori "no AAAA record" nei test.

**Record CAA (raccomandato, non obbligatorio)** — dice ai browser e
alle CA quali autorità possono emettere certificati per il dominio.
Per Let's Encrypt usato da Coolify:

| Nome              | Tipo | Flag | Tag    | Valore             |
| ----------------- | ---- | ---- | ------ | ------------------ |
| `tuodominio.it`   | CAA  | 0    | issue  | `letsencrypt.org`  |

> Se non imposti il CAA, di default qualunque CA pubblica può emettere
> certificati: funziona lo stesso, ma il CAA è una buona igiene di
> sicurezza.

**Wildcard (opzionale)** — se prevedi molti sottodomini gestiti da
Coolify, un solo record `*` li copre tutti:

| Nome              | Tipo | Valore           |
| ----------------- | ---- | ---------------- |
| `*.tuodominio.it` | A    | IP pubblico VPS  |

Il certificato wildcard però richiede Let's Encrypt **DNS-01**, che
serve un'API token al tuo provider DNS (vedi §5.3).

---

## 3. Registrar: delegare il dominio a Cloudflare

Se scegli Cloudflare come DNS (consigliato):

1. Crea un account gratuito su <https://dash.cloudflare.com>.
2. **Add a site → tuodominio.it → piano Free**.
3. Cloudflare importa automaticamente i record esistenti dal registrar.
   Rivedili: se ne manca qualcuno (MX della mail, TXT SPF/DKIM, ecc.)
   aggiungilo ora.
4. Cloudflare ti dà **due nameserver** (es. `aron.ns.cloudflare.com`
   e `beth.ns.cloudflare.com`).
5. Vai nel pannello del **registrar** del dominio → sezione
   **Nameserver / DNS** → sostituisci i nameserver attuali con quelli
   di Cloudflare.
6. Attendi la propagazione (da pochi minuti a 24 h). Cloudflare ti
   manda un'email quando la zona è attiva.

> Chi non passa da Cloudflare: salta al §6.

---

## 4. Cloudflare: creare i record A

Dentro la dashboard Cloudflare, **zona tuodominio.it → DNS → Records**:

```
Type: A     Name: bms       Content: <IP VPS>   Proxy status: DNS only
Type: A     Name: coolify   Content: <IP VPS>   Proxy status: DNS only
```

> `Name: bms` si espande in `bms.tuodominio.it` — Cloudflare
> auto-completa il dominio di zona.

**Proxy status: DNS only** (icona grigia, nuvola non arancione) è
**obbligatorio al primo avvio**, per due motivi:

1. Let's Encrypt HTTP-01 — il metodo usato da Coolify di default —
   richiede di raggiungere direttamente l'origine (VPS) sulla porta
   80. Con proxy arancione attivo, Cloudflare intercetta la richiesta
   e la challenge fallisce.
2. Cloudflare in modalità "Proxied" forza HTTPS verso l'origine, ma al
   primo deploy la VPS non ha ancora il certificato → loop.

Una volta che Coolify ha emesso il certificato e il sito risponde in
HTTPS, puoi (opzionalmente) abilitare il proxy arancione seguendo §5.2.

### 4.1 Record CAA in Cloudflare

**DNS → Records → Add record → CAA**:

- Name: `@` (= dominio root)
- Tag: `issue`
- CA domain: `letsencrypt.org`
- Flags: `0`

---

## 5. Cloudflare: impostazioni opzionali ma consigliate

### 5.1 SSL/TLS mode → Full (strict)

**SSL/TLS → Overview → Encryption mode: Full (strict)**.

- `Flexible`: CF↔browser HTTPS, CF↔origine HTTP → **non usare**, rompe
  redirect e genera loop.
- `Full`: CF↔origine HTTPS ma accetta certificati self-signed.
- `Full (strict)`: CF↔origine HTTPS **con certificato valido**. È la
  modalità giusta una volta che Coolify ha emesso il cert Let's
  Encrypt.

### 5.2 Abilitare il proxy arancione (dopo)

Utile per: DDoS protection, cache statica, analytics. Vincoli:

- lascia `SSL/TLS mode = Full (strict)` (non `Flexible`);
- assicurati che Coolify abbia già un cert valido sul dominio;
- le WebSocket funzionano, ma lo streaming long-lived può andare in
  timeout dopo ~100 s — per bms-core non è un problema.

Se qualcosa si rompe dopo aver acceso il proxy: rimetti subito il
record su **DNS only** e indaga con calma — il rollback è istantaneo.

### 5.3 Wildcard / DNS-01 (solo se serve)

Per un certificato `*.tuodominio.it` Coolify (via Traefik) può usare
la challenge DNS-01 con un token API Cloudflare:

1. Cloudflare **My Profile → API Tokens → Create Token → Edit zone DNS**
   limitato alla zona `tuodominio.it`.
2. In Coolify **Settings → Instance → DNS challenge providers**
   (o a livello di singolo dominio): scegli Cloudflare, incolla il
   token.
3. Imposta il dominio della risorsa come `*.tuodominio.it` o come
   wildcard specifico.

Per il caso d'uso base bms-core (1-2 sottodomini) **non serve**: HTTP-01
basta.

---

## 6. Alternativa: DNS sul registrar (senza Cloudflare)

Se preferisci non delegare a Cloudflare (es. dominio `.it` su Aruba
che vuoi tenere tutto in un posto):

1. Nel pannello del registrar apri la sezione **DNS / Zona DNS /
   Gestione record**.
2. Crea gli stessi record della §2 (A per `bms`, A per `coolify`,
   eventuale CAA).
3. Tempi di propagazione: variano dal registrar (Aruba tipicamente
   1-4 h, OVH < 1 h, Register 1-2 h).
4. Il TTL consigliato in fase di setup è **300 s** (5 minuti): se
   sbagli un valore lo correggi in fretta. Quando il setup è stabile,
   alzalo a 3600 s o più.

Funzionalità aggiuntive offerte da Cloudflare (proxy, WAF, cache) non
sono disponibili: a livello "pubblica sito" è equivalente.

---

## 7. Coolify: usare il dominio appena creato

Sulla VPS assicurati che il firewall sia aperto (fatto in
[`01-setup-vps.md`](01-setup-vps.md) §4): 80 e 443 **devono** essere
raggiungibili pubblicamente per la challenge Let's Encrypt.

### 7.1 Dominio del pannello Coolify

**Settings → Instance → Fully Qualified Domain Name**:

```
https://coolify.tuodominio.it
```

Salva. Coolify riconfigura Traefik e richiede il cert Let's Encrypt.
Nei log (in basso a destra, icona "Server" → Logs) vedi la sequenza
ACME; se fallisce controlla la §8.

Quando risponde in HTTPS, chiudi la porta 8000:

```bash
sudo ufw delete allow 8000/tcp
sudo ufw reload
```

### 7.2 Dominio dell'applicazione

Fatto nello step 4 di [`02-setup-coolify-app.md`](02-setup-coolify-app.md):
sul servizio `app` → tab **Domains** → `https://bms.tuodominio.it`.
Il DNS deve già puntare alla VPS (§2) **prima** di salvare, altrimenti
la challenge parte e fallisce.

---

## 8. Verifica e troubleshooting

### 8.1 Comandi di verifica

Dal tuo computer (non dalla VPS — il lookup da dentro la VPS colpisce
i resolver locali e può nascondere problemi):

```bash
dig +short bms.tuodominio.it                 # deve stampare l'IP della VPS
dig +short coolify.tuodominio.it
dig CAA tuodominio.it +short                 # verifica CAA
dig NS tuodominio.it +short                  # conferma nameserver Cloudflare / registrar
```

Propagazione globale: <https://dnschecker.org> → inserisci il nome,
scegli tipo A. Se vedi l'IP corretto nella maggior parte dei nodi sei
pronto.

### 8.2 Verifica HTTP-01 raggiungibile

```bash
curl -I http://bms.tuodominio.it/.well-known/acme-challenge/test
```

Deve rispondere dalla VPS (404 di Traefik è **OK** — significa che la
richiesta è arrivata). Se ricevi invece un errore Cloudflare (520,
522, 1016) o la risposta di un altro server: il DNS non punta alla
VPS o il proxy Cloudflare è attivo.

### 8.3 Problemi ricorrenti

| Sintomo                                         | Causa probabile                                               | Fix                                                        |
| ----------------------------------------------- | ------------------------------------------------------------- | ---------------------------------------------------------- |
| Let's Encrypt: `urn:ietf:params:acme:error:connection` o timeout | record A sbagliato o non propagato; proxy CF acceso      | correggi record, metti CF su **DNS only**                   |
| Let's Encrypt: `rateLimited`                    | troppi tentativi falliti (5/ora per hostname, 50/settimana per dominio base) | aspetta, oppure usa `staging` durante il debug              |
| 522 / 1016 in browser (solo con proxy CF on)    | origine non risponde su 443 (cert mancante o porta chiusa)    | torna a DNS only, lascia generare il cert, poi riaccendi    |
| Redirect infinito HTTPS                         | Cloudflare SSL mode `Flexible`                                | impostare `Full (strict)`                                  |
| Dominio risolve ma non arriva al sito giusto    | vecchi record non puliti (es. `@` con IP condiviso)           | rimuovi record orfani, flush cache DNS (`dig +trace`)       |
| `NXDOMAIN` solo da alcune reti                  | propagazione non finita                                       | attendi o abbassa TTL preventivamente la prossima volta     |

Log utili in Coolify:

- **Settings → Instance → Logs**: emissione certificato del pannello.
- **Resource → Logs**: Traefik mostra le richieste ACME per i domini
  delle app.

---

## 9. Checklist finale

- [ ] Dominio ha nameserver corretti (CF o registrar) — `dig NS` lo conferma.
- [ ] Record A per `app` e `coolify` puntano all'IP della VPS.
- [ ] CAA per `letsencrypt.org` presente (raccomandato).
- [ ] Cloudflare (se usato) è in **DNS only** al primo deploy.
- [ ] SSL mode su Cloudflare = **Full (strict)** (o nessun CF attivo).
- [ ] UFW apre 80 e 443, 22 solo per SSH.
- [ ] Pannello Coolify risponde in `https://coolify.tuodominio.it`.
- [ ] La porta 8000 è stata chiusa al mondo.

Quando tutto è verde, torna a [`02-setup-coolify-app.md`](02-setup-coolify-app.md)
e procedi con la creazione dell'applicazione.
