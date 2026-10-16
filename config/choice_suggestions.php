<?php

// Local example lists. Aliases are matched as whole words or phrases.
return [
    'studies' => [
        'label' => 'Studies en opleidingen',
        'aliases' => ['studie', 'studies', 'opleiding', 'opleidingen', 'studierichting', 'studierichtingen', 'study', 'field of study', 'degree programme', 'education', 'major'],
        'values' => [
            'Informatica', 'Bedrijfskunde', 'Psychologie', 'Verpleegkunde', 'Werktuigbouwkunde',
            'Rechten', 'Geneeskunde', 'Communicatie', 'Accountancy', 'Pedagogiek',
            'Bouwkunde', 'Elektrotechniek', 'Biologie', 'Economie', 'Social Work',
            'Technische Informatica', 'Logistiek Management', 'Onderwijskunde', 'Wiskunde', 'Geschiedenis',
        ],
    ],
    'education_levels' => [
        'label' => 'Opleidingsniveaus',
        'aliases' => ['opleidingsniveau', 'opleidingsniveaus', 'onderwijsniveau', 'niveau opleiding', 'education level'],
        'values' => ['Basisonderwijs', 'Vmbo', 'Havo', 'Vwo', 'Mbo', 'Hbo', 'Wo'],
    ],
    'jobs' => [
        'label' => 'Beroepen en functies',
        'aliases' => ['beroep', 'beroepen', 'functie', 'functies', 'job', 'job title', 'occupation', 'profession'],
        'values' => [
            'Softwareontwikkelaar', 'Projectmanager', 'Docent', 'Verpleegkundige', 'Accountant',
            'Administratief medewerker', 'Monteur', 'Ontwerper', 'Data-analist', 'Accountmanager',
            'Adviseur', 'Onderzoeker', 'HR-medewerker', 'Logistiek medewerker', 'Klantenservicemedewerker',
        ],
    ],
    'departments' => [
        'label' => 'Afdelingen',
        'aliases' => ['afdeling', 'afdelingen', 'department', 'departments'],
        'values' => ['IT', 'Verkoop', 'Marketing', 'Financiën', 'HR', 'Logistiek', 'Klantenservice', 'Inkoop', 'Onderzoek', 'Administratie'],
    ],
    'colors' => [
        'label' => 'Kleuren',
        'aliases' => ['kleur', 'kleuren', 'color', 'colors', 'colour', 'colours'],
        'values' => ['Zwart', 'Wit', 'Grijs', 'Blauw', 'Rood', 'Groen', 'Geel', 'Oranje', 'Paars', 'Roze', 'Bruin', 'Zilver'],
    ],
    'countries' => [
        'label' => 'Landen',
        'aliases' => ['land', 'landen', 'country', 'countries'],
        'values' => ['Nederland', 'België', 'Duitsland', 'Frankrijk', 'Spanje', 'Italië', 'Portugal', 'Oostenrijk', 'Zwitserland', 'Denemarken', 'Zweden', 'Noorwegen', 'Finland', 'Polen', 'Ierland', 'Verenigd Koninkrijk', 'Canada', 'Verenigde Staten', 'Japan', 'Australië'],
    ],
    'languages' => [
        'label' => 'Talen',
        'aliases' => ['taal', 'talen', 'language', 'languages'],
        'values' => ['Nederlands', 'Engels', 'Duits', 'Frans', 'Spaans', 'Italiaans', 'Portugees', 'Pools', 'Zweeds', 'Arabisch', 'Chinees', 'Japans'],
    ],
    'programming_languages' => [
        'label' => 'Programmeertalen',
        'aliases' => ['programmeertaal', 'programmeertalen', 'programmeer taal', 'programming language', 'programming languages'],
        'values' => ['Python', 'JavaScript', 'TypeScript', 'PHP', 'Java', 'C#', 'C++', 'Go', 'Rust', 'Kotlin', 'Swift', 'Ruby', 'R', 'SQL'],
    ],
    'sports' => [
        'label' => 'Sporten',
        'aliases' => ['sport', 'sporten', 'sportsoort', 'sports'],
        'values' => ['Voetbal', 'Tennis', 'Hockey', 'Basketbal', 'Volleybal', 'Zwemmen', 'Hardlopen', 'Wielrennen', 'Fitness', 'Badminton', 'Judo', 'Turnen', 'Handbal', 'Schaatsen'],
    ],
    'hobbies' => [
        'label' => 'Hobby’s',
        'aliases' => ['hobby', 'hobbys', 'hobbies', 'vrijetijdsbesteding'],
        'values' => ['Lezen', 'Koken', 'Fotografie', 'Wandelen', 'Tuinieren', 'Gamen', 'Tekenen', 'Muziek maken', 'Reizen', 'Bakken', 'Schaken', 'Fietsen'],
    ],
    'transport' => [
        'label' => 'Vervoermiddelen',
        'aliases' => ['vervoermiddel', 'vervoermiddelen', 'vervoer', 'transport mode', 'mode of transport'],
        'values' => ['Auto', 'Fiets', 'Trein', 'Bus', 'Tram', 'Metro', 'Motor', 'Scooter', 'Elektrische fiets', 'Te voet'],
    ],
    'operating_systems' => [
        'label' => 'Besturingssystemen',
        'aliases' => ['besturingssysteem', 'besturingssystemen', 'operating system', 'os'],
        'values' => ['Windows 11', 'macOS', 'Ubuntu', 'Debian', 'Fedora', 'ChromeOS'],
    ],
    'payments' => [
        'label' => 'Betaalmethoden',
        'aliases' => ['betaalmethode', 'betaalmethoden', 'betaalwijze', 'betaalwijzen', 'payment method'],
        'values' => ['Bankoverschrijving', 'Creditcard', 'Contant', 'Pinbetaling', 'Automatische incasso', 'Op rekening'],
    ],
    'order_statuses' => [
        'label' => 'Bestelstatussen',
        'aliases' => ['bestelstatus', 'bestelling status', 'status bestelling', 'orderstatus', 'order status'],
        'values' => ['Nieuw', 'Bevestigd', 'In behandeling', 'Verzonden', 'Afgeleverd', 'Geannuleerd', 'Geretourneerd'],
    ],
    'invoice_statuses' => [
        'label' => 'Factuurstatussen',
        'aliases' => ['factuurstatus', 'factuur status', 'status factuur', 'invoice status', 'payment status', 'betaalstatus'],
        'values' => ['Concept', 'Verzonden', 'Openstaand', 'Gedeeltelijk betaald', 'Betaald', 'Vervallen', 'Gecrediteerd'],
    ],
    'statuses' => [
        'label' => 'Statussen',
        'aliases' => ['status', 'statussen', 'state'],
        'values' => ['Nieuw', 'Actief', 'In behandeling', 'Afgerond', 'Inactief'],
    ],
    'priorities' => [
        'label' => 'Prioriteiten',
        'aliases' => ['prioriteit', 'prioriteiten', 'priority'],
        'values' => ['Laag', 'Normaal', 'Hoog', 'Urgent'],
    ],
    'weekdays' => [
        'label' => 'Weekdagen',
        'aliases' => ['weekdag', 'weekdagen', 'weekday', 'day of week'],
        'values' => ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'],
    ],
    'months' => [
        'label' => 'Maanden',
        'aliases' => ['maand', 'maanden', 'month', 'months'],
        'values' => ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
    ],
];
