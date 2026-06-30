# Der Smarte Gemeinschaftskalender Changelog

Hier gibt es eine Übersicht der Änderungen zwischen den Versionen.

## 30.06.2026 - v1.5.0
- Bei der Adresseingabe lassen sich jetzt mit einem Klick die Adresse, die Organisation oder die meistbenutzten bisherigen Adressen übernehmen. #32
- [Eircodes](https://en.wikipedia.org/wiki/Postal_addresses_in_the_Republic_of_Ireland) können als Adressen hinterlegt werden #36

## 27.05.2026 - v1.4.1
- Fix: Verbesserte Anzeige von Zeitzonen.
    - Im öffentlichen Bereich gibt es jetzt einen Alert, wenn nicht Europe/Berlin Zeitzone vom Browser benutzt wird.
    - Im internen Bereich gibt es einen Hinweis auf die richtige Zeitzone.
- Fix: Möglicher Fehler beim Öffnen vom Dialog Komponente. 

## 20.05.2026 - v1.4.0

- Serien Termine:
    - Bei der Auswahl gibt es für die deutschen Bundesländer die Möglichkeit, auszuwählen, dass **keine** Termine in den Schulferien oder an Feiertagen in Deutschland erstellt werden.
    - Beim Erstellen kann der Wochentag sowie das monatliche Intervall (1., 2., 3., 4. oder letzter Tag des Monats) konfiguriert werden.
    - Ein kompletter Serientermin sowie alle damit angelegten Termine lassen sich auf einmal löschen.
- Kalenderdateien / Kalenderintegrationen
    - Lass sich komplett mit allen angelegten Terminen löschen.
    - Wenn möglich, werden die Adressen und Bilder im Termin hinterlegt.
- Werbemittelegenrator: 
    - Im Werbemittel-Generator lassen sich die einzelnen Elemente nun verschieben, entfernen und anpassen. Außerdem gibt es die Möglichkeit, Stempel und Formen hinzuzufügen. Bei der Auswahl von Terminen können jetzt einzelne Termine hinzugefügt werden. #18
    - Es wurde eine Liste aller Browser-Schriftarten hinzugefügt.  #13
- Für alle öffentlichen Seiten gibt es jetzt deutsche und englische Texte. Außerdem gibt es die Möglichkeit, diese Texte zu bearbeiten. #23
- Als Administrator können Sie über die Weboberfläche zusätzliche Texte für E-Mails konfigurieren. #21
- Über einen Button lässt sich steuern, ob Passwörter in Input-Feldern im Klartext angezeigt werden.
- Für den Header Navigation kann ein zweiter externer Link konfiguriert werden. 
- Auf der Übersichtsseite der Organisation wurden die Suche und die Anzeige verbessert.
- Die Anwendung hat eine bessere Responsive-Darstellung. In der Kalenderansicht gibt es beispielsweise auch eine tägliche Auswahl.
- Fix: Für angemeldete Besucher sollte es nach dem Bearbeiten zu keinen Caching-Problemen kommen.
- Fix: Verbesserte Validierung von Benutzer- und Organisationsnamen (#26).
- Beim Ansible Deployment kann die bestehende nginx Konfiguration überschrieben werden.
- Für die Konfiguration gibt es jetzt die `overwriteConfig.ts`. Mehr Informationen sehe unten. 

> Wichtig: Bei der individuellen Konfiguration einer Instanz gibt es Breaking Changes. Alle Änderungen werden jetzt in der `frontend/src/lib/overwriteConfig.ts` definiert. [Weitere Informationen dazu gibt es im Handbuch](https://der-smarte-gemeinschaftskalender.de/Entwicklungsbereich/Hosting/#instance-config)

## 11.02.2026 - v1.3.0
- Verbesserte Verarbeitung von Adressen beim Hochladen oder Importieren von iCal-Dateien.
- Proxy für das Laden der Karten-Kacheln

