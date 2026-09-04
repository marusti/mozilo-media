# PHP ZIP-Plattform

Eine einfache webbasierte Plattform zum **Hochladen, Verwalten und öffentlichen Herunterladen von ZIP-Dateien**.

Benutzer können ZIP-Dateien hochladen, Kategorien und Tags vergeben sowie einen eigenen Namen und eine Beschreibung hinzufügen. Eigene Uploads können im Dashboard bearbeitet oder gelöscht werden.

Öffentliche Besucher können die verfügbaren Dateien durchsuchen und herunterladen.

Administratoren haben zusätzlich Zugriff auf den Admin-Bereich und können dort alle Uploads verwalten.

## Voraussetzungen

* PHP 8+
* PHP-Erweiterung `fileinfo`
* Apache mit `.htaccess` oder eine entsprechend konfigurierte alternative Webserver-Umgebung
* Schreibrechte für `data/` und `uploads/`

## Funktionen

* Benutzerregistrierung und Login
* Passwort-Hashing mit PHP `password_hash()`
* Temporäre Login-Sperre nach mehreren Fehlversuchen
* Session- und CSRF-Schutz
* Benutzer- und Admin-Rollen
* ZIP-Upload
* Maximale Uploadgröße: **20 MB**
* Kategorien
* Tags
* Eigener Anzeigename für Dateien
* Dateibeschreibung
* Bearbeiten eigener Uploads
* Löschen eigener Uploads
* Öffentliche Dateiliste
* Suche und Filter
* Download-Zähler
* Administrationsbereich
* Admin-Löschen von Dateien

Die ZIP-Dateien werden **nicht entpackt**. Sie werden ausschließlich gespeichert und zum Download bereitgestellt.

## Installation

1. Alle Dateien auf den Webspace kopieren.
2. `data/` und `uploads/` müssen vom PHP-Prozess beschreibbar sein.
3. Vorhandene `.htaccess`-Dateien müssen beibehalten werden.
4. PHP-Upload-Limits passend zur maximalen Dateigröße konfigurieren.

Empfohlen:

```ini
upload_max_filesize = 20M
post_max_size = 21M
```

5. Die Anwendung über `index.php` aufrufen.
6. Benutzerkonto registrieren und anschließend anmelden.

> Die automatische Installation und Einrichtung befindet sich noch in Entwicklung.

## Sicherheit

Für einen produktiven Betrieb sollten mindestens folgende Punkte berücksichtigt werden:

* HTTPS verwenden
* Regelmäßige Backups von `data/` und den hochgeladenen Dateien erstellen
* PHP und Server regelmäßig aktualisieren
* Upload-Verzeichnis gegen direkte Ausführung von Dateien absichern
* Geeignete Rate-Limiting- und Schutzmaßnahmen für den Webserver verwenden
* Bei Nginx eine entsprechende Serverkonfiguration anstelle von `.htaccess` verwenden
* Zugangsdaten und Konfigurationsdateien niemals öffentlich zugänglich machen

## Datenhaltung

Die Anwendung verwendet derzeit **JSON-Dateien** zur Speicherung von Benutzern, Dateien und weiteren Einstellungen.

Eine Datenbank wie SQLite oder MySQL ist aktuell nicht erforderlich.

## Uploads

Es werden ausschließlich ZIP-Dateien akzeptiert.

Die Dateien werden mit einem zufällig erzeugten internen Dateinamen gespeichert. Der ursprüngliche Dateiname wird separat gespeichert und für die Anzeige bzw. den Download verwendet.

ZIP-Dateien werden nicht automatisch entpackt.

## Projektstatus

Das Projekt befindet sich aktuell in aktiver Entwicklung.

Die grundlegenden Funktionen für Benutzerverwaltung, Upload, Dateiverwaltung, öffentliche Downloads und Administration sind bereits vorhanden.

Eine automatische Installationsroutine sowie weitere Produktionsoptimierungen sind für einen späteren Entwicklungsschritt vorgesehen.

