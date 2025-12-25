# Deployment Guide - KitsDB

## ⚠️ IMPORTANTE: Da fare PRIMA del deployment

### 1. Pulire la Git History (Rimuovere password dai vecchi commit)

Le credenziali erano presenti nei commit precedenti. Prima di pushare, DEVI pulire la history:

```bash
# Backup del repository
cd ..
cp -r kitdb kitdb_backup

cd kitdb

# Rimuovi le credenziali dalla history usando git filter-repo
git filter-repo --replace-text expressions.txt --force

# Pulizia
git reflog expire --expire=now --all
git gc --prune=now --aggressive
```

### 2. Force Push (ATTENZIONE: Sovrascrive la history su GitHub)

```bash
git push origin --force --all
git push origin --force --tags
```

### 3. Cambiare TUTTE le Password (CRITICO!)

Poiché le vecchie password erano esposte su GitHub, DEVI cambiarle:

#### Database MySQL Netsons:
1. Vai in cPanel → Database MySQL
2. Cambia password per utente `YOUR_DB_NAME_HERE`
3. Nuova password: **[scegli una nuova password sicura]**

#### Database MySQL Aruba:
1. Accedi al pannello Aruba
2. Cambia password per database `YOUR_ARUBA_DB_NAME_HERE`
3. Nuova password: **[scegli una nuova password sicura]**

#### Admin Application:
1. Scegli una nuova password admin
2. Aggiorna il file `.env` sul server

---

## Deployment su Server di Produzione (Netsons)

### Opzione A: Deploy via Git (Raccomandato)

1. **SSH nel server** (se disponibile):
   ```bash
   ssh tuoutente@hostingssd79.netsons.net
   ```

2. **Pull delle modifiche**:
   ```bash
   cd /path/to/your/site
   git pull origin master
   ```

3. **Crea il file .env sul server**:
   ```bash
   cp .env.example .env
   nano .env
   ```

   Inserisci le credenziali:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=YOUR_DB_NAME_HERE
   DB_USER=YOUR_DB_NAME_HERE
   DB_PASS=[NUOVA_PASSWORD_QUI]

   ADMIN_USERNAME=admin
   ADMIN_PASSWORD=[NUOVA_PASSWORD_ADMIN_QUI]
   ```

4. **Verifica permessi**:
   ```bash
   chmod 600 .env  # Solo owner può leggere
   ```

### Opzione B: Deploy via FTP/File Manager

1. **Accedi al File Manager di cPanel** o usa FTP

2. **Upload dei file modificati**:
   - `config.php`
   - `config_aruba.php`
   - `setup_admin.php`
   - `gen_hash.php`
   - `testconn.php`
   - `testconn2.php`
   - `env_loader.php`
   - `.gitignore`
   - `SETUP.md`

3. **Crea il file `.env`** nella root del sito:
   - Puoi copiare `.env.example` e rinominarlo in `.env`
   - Modifica inserendo le NUOVE credenziali (non quelle vecchie!)
   - Imposta `DB_HOST=localhost` (non l'hostname remoto!)

4. **Verifica permessi del file .env**:
   - Nel File Manager, click destro su `.env` → Permissions → 600

---

## Dopo il Deployment

### 1. Testa la connessione database

Vai su: `https://tuodominio.it/testconn2.php`

Dovresti vedere: ✅ Connessione riuscita!

### 2. Testa il login admin

1. Vai su: `https://tuodominio.it/login.php`
2. Username: `admin`
3. Password: [la nuova password che hai impostato in .env]

### 3. Elimina i file di test (IMPORTANTE!)

Sul server di produzione, elimina questi file per sicurezza:
- `testconn.php`
- `testconn2.php`
- `test_env.php`
- `test_config.php`
- `setup_admin.php` (dopo aver creato l'admin)
- `gen_hash.php` (dopo aver usato)

---

## Risolvi gli Incident su GitGuardian

1. Vai su https://dashboard.gitguardian.com
2. Per ogni incident:
   - Click su "Mark as..."
   - Seleziona **"Credential revoked"** (hai cambiato le password)
   - Aggiungi nota: "Passwords changed and removed from git history"

---

## Checklist Finale

- [ ] Git history pulita (password rimosse dai vecchi commit)
- [ ] Force push su GitHub completato
- [ ] Password database MySQL cambiate
- [ ] Password admin cambiata
- [ ] File `.env` creato sul server con NUOVE password
- [ ] `DB_HOST=localhost` nel `.env` di produzione
- [ ] Testata connessione database su produzione
- [ ] Testato login admin su produzione
- [ ] File di test eliminati da produzione
- [ ] GitGuardian incidents risolti

---

## Note di Sicurezza

- **MAI** committare il file `.env` su git
- **MAI** usare le vecchie password (erano pubbliche su GitHub)
- Cambia le password ogni 3-6 mesi
- Mantieni backup regolari del database
- Monitora GitGuardian per nuove esposizioni
