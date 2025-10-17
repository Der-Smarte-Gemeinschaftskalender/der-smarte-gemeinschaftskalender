# Der Smarte Gemeinschaftskalender

Alle Termine im Blick – die Open-Source-Lösung für eine starke Gemeinschaft.

[Dokumentation](https://der-smarte-gemeinschaftskalender.de/)

Ein Projekt von der [**54 Grad Software GmbH**](https://54gradsoftware.de), [**Smart City Amt Süderbrarup**](https://smartcityamtsuederbrarup.de/) und dem [**Amt Süderbrarup**](https://www.amt-suederbrarup.de/).

Das Projekt basiert auf [Mobilizon](https://mobilizon.org/). Mehr Informationen zu den Technologien gibt es in der [Dokumentatation](https://mobilizon.org/).

---

## Default Logins lokale entwicklung

| Username | Passwort | Rolle |
| -- | -- | -- |
| dsg-admin@example.com | Sicher1234! | Admin |
| dsg-user@example.com | Sicher1234! | User | 

## E2E Tests 

Alle commands für e2e tests müssen im `e2eTests` Verzeichnis ausgeführt werden. 

### Setup

Es muss eine .env Datei erstellt werden, diese kann auf der `example.env` basiert werden.

```bash
cd e2eTests
cp example.env .env
# .env Datei entsprechend anpassen
```

Zuerst playwright installieren
```bash
npx playwright install
```

### Tests ausführen

#### Alle Tests ausführen
```bash
npx playwright test
```

#### Tests im UI Mode (empfohlen für Entwicklung)
```bash
npx playwright test --ui
```
Im UI Mode muss dann der "Start" Button oben links angeklickt werden um den test zu starten.

#### Spezifische Test-Datei ausführen
```bash
npx playwright test basicTests.spec.ts
npx playwright test advancedTests.spec.ts
npx playwright test advancedSerialTests.spec.ts
npx playwright test advancedSingleTests.spec.ts
```

#### Tests in einem bestimmten Browser ausführen
```bash
npx playwright test --project=chromium
npx playwright test --project=firefox  
npx playwright test --project=webkit
```

#### Headed Mode (für Entwicklung)
```bash
npx playwright test --headed
```

### Mehr Informationen zu e2e Tests
[Playwright Documentation](https://playwright.dev/)