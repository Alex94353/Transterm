# Transterm

Modernizácia webovej terminologickej databázy Transterm vytvorená ako bakalárska práca. Projekt slúži na správu glosárov, termínov, prekladov a súvisiacich metadát vo webovom prostredí. Systém je určený predovšetkým pre akademické a inštitucionálne použitie, kde je dôležitá kontrola kvality údajov, používateľských rolí a schvaľovania obsahu.

## O projekte

Transterm je webová aplikácia na evidenciu a správu terminologických záznamov. Umožňuje vytvárať glosáre, spravovať termíny a ich preklady, publikovať schválený obsah a rozlišovať medzi verejnou a internou časťou systému.

Projekt vznikol ako modernizácia pôvodného staršieho systému Transterm. Nová verzia je rozdelená na backend a frontend časť a používa moderný technologický stack vhodný pre ďalší rozvoj, testovanie a nasadenie.

## Technologický stack

### Backend
- Laravel 12
- PHP 8.2+
- MySQL
- Bearer token autentifikácia cez Laravel Sanctum personal access tokens
- Spatie Laravel Permission

### Frontend
- Vue 3
- Vite
- Pinia
- Element Plus

## Architektúra repozitára

Repozitár je rozdelený na viacero častí:

```text
.
├── transterm-backend
└── transterm-frontend

```

Jednotlivé časti repozitára majú nasledovné určenie:

transterm-backend – backendová časť aplikácie postavená na frameworku Laravel; obsahuje API, aplikačnú logiku, autentifikáciu, správu rolí a prácu s databázou.
transterm-frontend – frontendová časť aplikácie postavená na Vue 3; obsahuje používateľské rozhranie, routovanie, stav aplikácie a komunikáciu s backend API.