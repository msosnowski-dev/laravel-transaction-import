# Aplikacja importu transakcji bankowych

Aplikacja w Laravel 12 i Vue 3 do importu, walidacji i zapisu transakcji bankowych z plików CSV, JSON oraz XML.

## Funkcjonalności

- Import plików w formatach: CSV, JSON, XML(wzorzec Factory i dedykowane parsery dla każdego formatu).
- Walidacja danych transakcji:
  - Numer rachunku: walidacja formatu IBAN i sumy kontrolnej (algorytm ISO 7064 MOD 97-10, długości dla poszczególnych krajów m.in. PL, DE, GB).
  - Kwota: weryfikacja wartości dodatnich (> 0).
  - Przeliczanie kwot z jednostek podrzędnych (grosze/centy): opcja przeliczania wartości całkowitych na format dziesiętny (np. 150000 -> 1500.00) przy użyciu biblioteki `Brick\Math\BigDecimal`.
  - Waluta: 3-literowy kod ISO (np. PLN, EUR, USD).
  - Data: poprawny format daty.
- Bezpieczeństwo i wydajność:
  - Odrzucanie pustych plików (0 bajtów).
  - Strumieniowe przetwarzanie wszystkich formatów za pomocą generatorów (`yield`): CSV (`fgetcsv`), XML (`XMLReader`) oraz dużych plików JSON (`json-machine`), co zapobiega wyczerpaniu pamięci RAM.
  - Zapis poprawnych rekordów w transakcji bazy danych (`DB::transaction`) z buforowaniem (`chunk insert`).
  - Zapis odrzuconych rekordów do tabeli logów (`import_logs`) z opisem błędu.
- Interfejs użytkownika (Vue 3, Vite, Tailwind CSS):
  - Formularz wgrywania plików (drag & drop).
  - Tabela wykonanych importów wraz ze statystykami.
  - Okno szczegółów importu:
    - Zakładka z logami błędów (z wyszukiwarką).
    - Opcjonalna zakładka z poprawnie zaimportowanymi transakcjami (sterowana konfiguracją).
  - Pliki do szybkiego testowania przykładowych plików.

## Wymagania

- PHP >= 8.2 (rozszerzenie `pdo_sqlite`)
- Composer
- Node.js & npm

## Instalacja i uruchomienie

1. Klonowanie repozytorium i instalacja zależności:
```bash
git clone https://github.com/msosnowski-dev/laravel-transaction-import.git

composer install
npm install
```

2. Konfiguracja środowiska:
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

3. Uruchomienie aplikacji:

W pierwszym terminalu:
```bash
php artisan serve
```

W drugim terminalu:
```bash
npm run dev
```

Aplikacja dostępna jest pod adresem: `http://localhost:8000`

## Konfiguracja (config/import.php)

Ustawienia importu można dostosować w pliku `config/import.php` lub przez zmienne środowiskowe w `.env`:

- `amount_in_minor_units` (`IMPORT_AMOUNT_IN_MINOR_UNITS`, domyślnie: `true`)
  Określa, czy kwoty w plikach wejściowych są podane w groszach/centach. Gdy włączone, kwoty dzielone są przez 100 za pomocą `BigDecimal` (np. 150000 -> 1500.00).

- `json.show_transactions` (`IMPORT_SHOW_TRANSACTIONS`, domyślnie: `true`)
  Określa, czy API i modal szczegółów mają ładować i pokazywać zakładkę poprawnych transakcji.

- `json.streaming_threshold_mb` (`IMPORT_JSON_STREAMING_THRESHOLD_MB`, domyślnie: `10`)
  Rozmiar pliku JSON w MB, od którego włącza się parser strumieniowy.

## Struktura kodu

```text
app/
├── DTO/
│   ├── TransactionDTO.php          # DTO wiersza transakcji
│   └── ImportResultDTO.php         # DTO wyniku importu
├── Enums/
│   ├── FileType.php                # Obsługiwane typy plików
│   └── ImportStatus.php            # Statusy importu (success, partial, failed, processing)
├── Http/
│   ├── Controllers/Api/
│   │   └── ImportController.php    # API importu
│   ├── Requests/
│   │   └── ImportRequest.php       # Walidacja pliku w requeście
│   └── Resources/
│       ├── ImportResource.php      # Resource importu
│       ├── ImportLogResource.php   # Resource logów błędów
│       └── TransactionResource.php # Resource transakcji
├── Models/
│   ├── Import.php                  # Model nagłówka importu
│   ├── ImportLog.php               # Model błędu importu
│   └── Transaction.php             # Model transakcji
└── Services/
    ├── ImportService.php           # Serwis procesu importu i zapisu
    ├── Parsers/
    │   ├── FileParserFactory.php   # Fabryka parserów
    │   ├── FileParserInterface.php # Interfejs parsera
    │   ├── CsvFileParser.php       # Parser CSV
    │   ├── JsonFileParser.php      # Parser JSON
    │   └── XmlFileParser.php       # Parser XML
    └── Validation/
        ├── IbanValidator.php       # Walidacja IBAN (ISO 7064 MOD 97-10)
        └── TransactionValidator.php# Walidacja pól rekordu
```

---

# Bank Transactions Import Application

An application in Laravel 12 and Vue 3 for importing, validating, and saving bank transactions from CSV, JSON, and XML files.

## Features

- File import in formats: CSV, JSON, XML (Factory pattern and dedicated parsers for each format).
- Transaction data validation:
  - Account number: IBAN format and checksum validation (ISO 7064 MOD 97-10 algorithm, lengths for specific countries including PL, DE, GB).
  - Amount: verification of positive values (> 0).
  - Minor currency units conversion (cents/grosze): option to convert integer values to decimal format (e.g., 150000 -> 1500.00) using the `Brick\Math\BigDecimal` library.
  - Currency: 3-letter ISO code (e.g., PLN, EUR, USD).
  - Date: valid date format.
- Security and performance:
  - Rejection of empty files (0 bytes).
  - Stream processing of all formats using generators (`yield`): CSV (`fgetcsv`), XML (`XMLReader`), and large JSON files (`json-machine`), preventing RAM exhaustion.
  - Saving valid records within a database transaction (`DB::transaction`) with chunking (`chunk insert`).
  - Saving rejected records to the error log table (`import_logs`) with an error description.
- User interface (Vue 3, Vite, Tailwind CSS):
  - File upload form (drag & drop).
  - Table of completed imports with statistics.
  - Import details window:
    - Error logs tab (with search functionality).
    - Optional tab with successfully imported transactions (configuration-controlled).
  - Files for quick testing with sample data.

## Requirements

- PHP >= 8.2 (`pdo_sqlite` extension)
- Composer
- Node.js & npm

## Installation and Setup

1. Clone the repository and install dependencies:
```bash
git clone https://github.com/msosnowski-dev/laravel-transaction-import.git

composer install
npm install
```

2. Environment configuration:
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

3. Running the application:

In the first terminal:
```bash
php artisan serve
```

In the second terminal:
```bash
npm run dev
```

The application is available at: `http://localhost:8000`

## Configuration (config/import.php)

Import settings can be customized in `config/import.php` or via environment variables in `.env`:

- `amount_in_minor_units` (`IMPORT_AMOUNT_IN_MINOR_UNITS`, default: `true`)
  Specifies whether amounts in input files are given in minor units (cents/grosze). When enabled, amounts are divided by 100 using `BigDecimal` (e.g., 150000 -> 1500.00).

- `json.show_transactions` (`IMPORT_SHOW_TRANSACTIONS`, default: `true`)
  Specifies whether the API and details modal should load and display the valid transactions tab.

- `json.streaming_threshold_mb` (`IMPORT_JSON_STREAMING_THRESHOLD_MB`, default: `10`)
  JSON file size in MB above which the streaming parser is enabled.

## Code Structure

```text
app/
├── DTO/
│   ├── TransactionDTO.php          # Transaction row DTO
│   └── ImportResultDTO.php         # Import result DTO
├── Enums/
│   ├── FileType.php                # Supported file types
│   └── ImportStatus.php            # Import statuses (success, partial, failed, processing)
├── Http/
│   ├── Controllers/Api/
│   │   └── ImportController.php    # Import API
│   ├── Requests/
│   │   └── ImportRequest.php       # Request file validation
│   └── Resources/
│       ├── ImportResource.php      # Import resource
│       ├── ImportLogResource.php   # Error log resource
│       └── TransactionResource.php # Transaction resource
├── Models/
│   ├── Import.php                  # Import header model
│   ├── ImportLog.php               # Import error model
│   └── Transaction.php             # Transaction model
└── Services/
    ├── ImportService.php           # Import and persistence service
    ├── Parsers/
    │   ├── FileParserFactory.php   # Parser factory
    │   ├── FileParserInterface.php # Parser interface
    │   ├── CsvFileParser.php       # CSV parser
    │   ├── JsonFileParser.php      # JSON parser
    │   └── XmlFileParser.php       # XML parser
    └── Validation/
        ├── IbanValidator.php       # IBAN validation (ISO 7064 MOD 97-10)
        └── TransactionValidator.php# Record field validation
```
