<?php
/**
 * Internationalisation file for GeoData extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Max Semenik
 */
$messages['en'] = array(
	'geodata-desc' => 'Adds geographical coordinates storage and retrieval functionality',
	'geodata-bad-input' => 'Invalid arguments have been passed to the <nowiki>{{#coordinates:}}</nowiki> function',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: invalid latitude',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: invalid longitude',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: unrecognised type "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: unrecognised globe "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: invalid region code format',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: cannot have more than one primary tag per page',
	'geodata-limit-exceeded' => 'The limit of $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|tag|tags}} per page has been exceeded',
	'geodata-broken-tags-category' => 'Pages with malformed coordinate tags',
	'geodata-unknown-type-category' => 'Pages with unknown type of coordinates',
	'geodata-unknown-globe-category' => 'Pages with unknown globe value',
	'geodata-unknown-region-category' => 'Pages with invalid region value',
);

/** Message documentation (Message documentation)
 * @author Max Semenik
 * @author SPQRobin
 * @author Shirayuki
 */
$messages['qqq'] = array(
	'geodata-desc' => '{{desc|name=Geo Data|url=http://www.mediawiki.org/wiki/Extension:GeoData}}',
	'geodata-bad-globe' => 'Terrestrial body on which the coordinate resides. By default, Earth is assumed. Other globes include earth, mercury, venus, moon, mars, ...',
	'geodata-limit-exceeded' => '$1 is a number',
	'geodata-broken-tags-category' => 'Name of the tracking category',
	'geodata-unknown-type-category' => 'Name of the tracking category',
	'geodata-unknown-globe-category' => 'Name of the tracking category',
	'geodata-unknown-region-category' => 'Name of the tracking category',
);

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'geodata-primary-coordinate' => 'أساسي',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'geodata-desc' => "Amiesta una función d'almacenamientu y recuperación de coordenaes xeográfiques.",
	'geodata-bad-input' => 'Se pasaron argumentos inválidos a la función <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: Llatitú inválida',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: Llonxitú inválida',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: Tipu "$1" non reconocíu',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globu "$1" non reconocíu',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: formatu inválidu de códigu de rexón',
	'geodata-multiple-primary' => "<nowiki>{{#coordinates:}}</nowiki>: nun pue haber más d'una etiqueta primaria por páxina",
	'geodata-limit-exceeded' => 'Se pasó del llímite de $1 {{PLURAL:$1|etiqueta|etiquetes}} <nowiki>{{#coordinates:}}</nowiki> por páxina',
	'geodata-broken-tags-category' => 'Páxines con etiquetes de coordenadas con formatu incorreutu',
	'geodata-unknown-type-category' => 'Páxines con coordenaes de tipu desconocíu',
	'geodata-unknown-globe-category' => 'Páxines con valores de globu desconocíos',
	'geodata-unknown-region-category' => 'Páxines con valores de rexón inválidos',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author EugeneZelenko
 * @author Jim-by
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'geodata-desc' => 'Дадае функцыянальнасьць захаваньня і атрыманьня геаграфічных каардынатаў.',
	'geodata-bad-input' => 'У функцыю <nowiki>{{#coordinates:}}</nowiki> быў перададзены няслушны аргумэнт',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: няслушная шырата',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: няслушная даўгата',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: нераспазнаны тып «$1»',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: нераспазнаны астранамічны аб’екі «$1»',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: няслушны фармат коду рэгіёну',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: нельга мець больш за адну першасную метку на старонцы',
	'geodata-limit-exceeded' => 'Было перавышана абмежаваньне ў $1 {{PLURAL:$1|выклік|выклікі|выклікаў}} <nowiki>{{#coordinates:}}</nowiki> на старонку',
	'geodata-broken-tags-category' => 'Старонкі зь няслушнымі каардынатнымі меткамі',
	'geodata-unknown-type-category' => 'Старонкі зь невядомымі тыпамі каардынатаў',
	'geodata-unknown-globe-category' => 'Старонкі зь невядомым глябальным значэньнем',
	'geodata-unknown-region-category' => 'Старонкі зь няслушным рэгіёнам',
);

/** Breton (brezhoneg)
 * @author Y-M D
 */
$messages['br'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki> : ledred direizh',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki> : hedred direizh',
);

/** Catalan (català)
 * @author Vriullop
 */
$messages['ca'] = array(
	'geodata-desc' => "Afegeix la funcionalitat d'emmagatzematge i recuperació de coordenades geogràfiques",
	'geodata-bad-input' => "S'han passat arguments no vàlids a la funció <nowiki>{{#coordinates:}}</nowiki>",
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitud no vàlida',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitud no vàlida',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tipus «$1» no reconegut',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globus «$1» no reconegut',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format de codi de regió no vàlid',
	'geodata-multiple-primary' => "<nowiki>{{#coordinates:}}</nowiki>: no hi pot haver més d'una etiqueta primària per pàgina",
	'geodata-limit-exceeded' => "S'ha superat el límit {{PLURAL:$1|d'$1 etiqueta|de $1 etiquetes}} <nowiki>{{#coordinates:}}</nowiki> per pàgina",
	'geodata-broken-tags-category' => 'Pàgines amb etiquetes de coordenades amb format incorrecte',
	'geodata-unknown-type-category' => 'Pàgines amb tipus de coordenades desconegut',
	'geodata-unknown-globe-category' => 'Pàgines amb valor de globus desconegut',
	'geodata-unknown-region-category' => 'Pàgines amb valor de regió no vàlid',
);

/** Czech (česky)
 * @author Jkjk
 * @author Mormegil
 */
$messages['cs'] = array(
	'geodata-desc' => 'Přidá funkci samostatného uchovávání a vyhledávání zeměpisných souřadnic',
	'geodata-bad-input' => 'Funkci <nowiki>{{#coordinates:}}</nowiki> byly předány neplatné parametry',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: nesprávná zeměpisná šířka',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: nesprávná zeměpisná délka',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: nerozpoznán typ "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: nerozpoznána planeta "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: nesprávný kód regionu',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: na stránce není možné mít více než jeden hlavní štítek',
	'geodata-limit-exceeded' => 'Limit $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|štítku|štítků}} na stránku byl překročen',
	'geodata-broken-tags-category' => 'Stránky s nesprávnými štítky zeměpisných souřadnic',
	'geodata-unknown-type-category' => 'Stránky s neznámým typem zeměpisných souřadnic',
	'geodata-unknown-globe-category' => 'Stránky s neznámým označením planety',
	'geodata-unknown-region-category' => 'Stránky s nesprávným regionem',
);

/** German (Deutsch)
 * @author Kghbln
 */
$messages['de'] = array(
	'geodata-desc' => 'Ergänzt Funktionen zum Speichern und Abrufen geografischer Koordinaten',
	'geodata-bad-input' => 'Es wurden ungültige Argumente an die Funktion <code><nowiki>{{#coordinates:}}</nowiki></code> übergeben.',
	'geodata-bad-latitude' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: ungültige Längenangabe',
	'geodata-bad-longitude' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: ungültige Breitenangabe',
	'geodata-bad-type' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: unbekannter Koordinatentyp „$1“',
	'geodata-bad-globe' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: unbekannter Globus „$1“',
	'geodata-bad-region' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: ungültiges Regionscodeformat',
	'geodata-multiple-primary' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Es kann nicht mehr als eine primäre Auszeichnung angegeben werden.',
	'geodata-limit-exceeded' => 'Die Begrenzung zur Funktion <code><nowiki>{{#coordinates:}}</nowiki></code> von {{PLURAL:$1|einer Auszeichnung|$1 Auszeichnungen}} je Seite, wurde überschritten.',
	'geodata-broken-tags-category' => 'Seiten mit fehlerhaften Auszeichnungen zu Koordinaten',
	'geodata-unknown-type-category' => 'Seiten mit unbekanntem Koordinatentyp',
	'geodata-unknown-globe-category' => 'Seiten mit unbekanntem Codewert für den Globus',
	'geodata-unknown-region-category' => 'Seiten mit unbekanntem Codewert für die Region',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'geodata-primary-coordinate' => 'Sıfteyên',
);

/** British English (British English)
 * @author Amire80
 */
$messages['en-gb'] = array(
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: unrecognised type "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: unrecognised globe "$1"',
);

/** Spanish (español)
 * @author Armando-Martin
 */
$messages['es'] = array(
	'geodata-desc' => 'Agrega la funcionalidad de almacenamiento y recuperación de coordenadas geográficas',
	'geodata-bad-input' => 'Se han pasado argumentos no válidos a la función <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitud no válida',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitud no válida',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tipo "$1" no reconocido',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globo "$1" no reconocido',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: formato de código de región no válido',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: no puede tener más de una etiqueta principal por página',
	'geodata-limit-exceeded' => 'El límite de $1 {{PLURAL:$1|etiqueta|etiquetas}} <nowiki>{{#coordinates:}}</nowiki> por página ha sido superado',
	'geodata-broken-tags-category' => 'Páginas con etiquetas de coordenadas con formato incorrecto',
	'geodata-unknown-type-category' => 'Páginas con tipo de coordenadas desconocido',
	'geodata-unknown-globe-category' => 'Páginas con valor de mundo desconocido',
	'geodata-unknown-region-category' => 'Páginas con valor de región no válido',
);

/** Estonian (eesti)
 * @author Pikne
 */
$messages['et'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: vigane laius',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: vigane pikkus',
);

/** Persian (فارسی)
 * @author Mjbmr
 * @author جواد
 */
$messages['fa'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: عرض جغرافیایی نامعتبر',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: طول جغرافیایی نامعتبر',
);

/** Finnish (suomi)
 * @author VezonThunder
 */
$messages['fi'] = array(
	'geodata-desc' => 'Lisää maantieteellisten koordinaattien säilöntä- ja hakutoiminnot',
	'geodata-bad-input' => '<nowiki>{{#coordinates:}}</nowiki>-funktioon on annettu virheellisiä argumentteja',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: virheellinen leveysaste',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: virheellinen pituusaste',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tuntematon tyyppi "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: tuntematon karttapallo "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: virheellinen aluekoodin muoto',
);

/** French (français)
 * @author Gomoko
 */
$messages['fr'] = array(
	'geodata-desc' => "Ajoute la fonctionnalité de stockage et d'extraction des coordonnées géographiques.",
	'geodata-bad-input' => 'Des arguments non valides ont été transmis à la focntion <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitude invalide',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitude invalide',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: type "$1" non reconnu',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globe "$1" non reconnu',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format du code de région invalide',
	'geodata-multiple-primary' => "<nowiki>{{#coordinates:}}</nowiki>: impossible d'avoir plus d'une balise primaire par page",
	'geodata-limit-exceeded' => 'La limite de $1 {{PLURAL:$1|balise|balises}} <nowiki>{{#coordinates:}}</nowiki> par page a été dépassée',
	'geodata-broken-tags-category' => 'Pages avec des balises de coordonnées mal formées',
	'geodata-unknown-type-category' => 'Pages avec un type de coordonnées inconnu',
	'geodata-unknown-globe-category' => 'Pages avec une valeur de globe inconnue',
	'geodata-unknown-region-category' => 'Pages avec une valeur de région invalide',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'geodata-desc' => 'Engade unha funcionalidade de almacenamento e de extracción de coordenadas xeográficas.',
	'geodata-bad-input' => 'Pasáronselle argumentos incorrectos á función <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: Latitude incorrecta',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: Lonxitude incorrecta',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: Non se recoñece o tipo "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: Non se recoñece o globo "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: Formato do código de rexión incorrecto',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: Non pode haber máis dunha etiqueta primaria por páxina',
	'geodata-limit-exceeded' => 'Superouse o límite de $1 {{PLURAL:$1|etiqueta|etiquetas}} <nowiki>{{#coordinates:}}</nowiki> por páxina',
	'geodata-broken-tags-category' => 'Páxinas con etiquetas de coordenadas con formato incorrecto',
	'geodata-unknown-type-category' => 'Páxinas con coordenadas descoñecidas',
	'geodata-unknown-globe-category' => 'Páxinas con valores descoñecidos',
	'geodata-unknown-region-category' => 'Páxinas con valores de rexión incorrectos',
);

/** Hebrew (עברית)
 * @author Amire80
 * @author Inkbug
 * @author YaronSh
 */
$messages['he'] = array(
	'geodata-desc' => 'מוסיף אפשרות לאכסון ואחזור של נקודות ציון גאוגרפיות',
	'geodata-bad-input' => 'פרמטרים בלתי־תקינים הועברו לפונקציה <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: קו רוחב בלתי־תקין',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: קו אורך בלתי־תקין',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: סוג לא מוּכר "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: גלובוס לא מוּכר "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: תסדיר קוד אזור בלתי־תקין',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: אי־אפשר שיהיה יותר מתג ראשי אחד בכל דף',
	'geodata-limit-exceeded' => 'נחצתה המגבלה של {{PLURAL:|תג <nowiki>{{#coordinates:}}</nowiki> אחד|$1 תגי <nowiki>{{#coordinates:}}</nowiki>}} לדף',
	'geodata-broken-tags-category' => 'דפים עם תגי נקודות ציון בלתי־תקינים',
	'geodata-unknown-type-category' => 'דפים עם סוג בלתי־ידוע של נקודות ציון',
	'geodata-unknown-globe-category' => 'דפים עם ערך גלובוס בלתי־ידוע',
	'geodata-unknown-region-category' => 'דפים עם ערך אזור בלתי־ידוע',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'geodata-desc' => 'Přidawa funkcije za składowanje a wotwołowanje geografiskich koordinatow.',
	'geodata-bad-input' => 'Njepłaćiwe argumenty su so funkciji <nowiki>{{#coordinates:}}</nowiki> přepodali',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: njepłaćiwa šěrina',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: njepłaćiwa dołhosć',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: Njespóznaty typ "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: Njespóznaty globus "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: njepłaćiwy format regionalneho koda',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: njemóže wjace hač jednu primarnu marku na stronu měć',
	'geodata-limit-exceeded' => 'Limit $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|marki|markow|markow|markow}} na stronu je so překročił',
	'geodata-broken-tags-category' => 'Strony z deformowanymi koordinatowymi markami',
	'geodata-unknown-type-category' => 'Strony z njeznatym typom koordinatow',
	'geodata-unknown-globe-category' => 'Strony z njeznatej globusowej hódnotu',
	'geodata-unknown-region-category' => 'Strony z njepłaćiwej regionowej hódnotu',
);

/** Hungarian (magyar)
 * @author Dj
 */
$messages['hu'] = array(
	'geodata-desc' => 'Földrajzi koordináták tárolásának és visszakeresésének lehetősége',
	'geodata-bad-input' => 'Érvénytelen argumentumok átadva a <nowiki>{{#coordinates:}}</nowiki> függvénynek',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: érvénytelen szélesség',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: érvénytelen hosszúság',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: ismeretlen típus "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: érvénytelen régiókód formátum',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: nem lehet egynél több elsődleges címke oldalanként',
	'geodata-limit-exceeded' => 'Meghaladta a laponként megengedett $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|címkét}}.',
	'geodata-broken-tags-category' => 'Hibás koordináta címkékkel rendelkező oldalak',
	'geodata-unknown-type-category' => 'Ismeretlen típusú koordinátákat tartalmazó oldalak',
	'geodata-unknown-region-category' => 'Érvénytelen régió értéket tartalmazó oldalak',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'geodata-desc' => 'Adde functionalitate pro immagazinage e recuperation de coordinatas geographic.',
	'geodata-bad-input' => 'Parametros invalide ha essite passate al function <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitude invalide',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitude invalide',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: typo "$1" non recognoscite',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globo "$1" non recognoscite',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: formato de codice de region invalide',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: non pote haber plus de un etiquetta primari per pagina',
	'geodata-limit-exceeded' => 'Le limite de $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|etiquetta|etiquettas}} per pagina ha essite excedite',
	'geodata-broken-tags-category' => 'Paginas con etiquettas mal formate de coordinatas',
	'geodata-unknown-type-category' => 'Paginas con typo incognite de coordinatas',
	'geodata-unknown-globe-category' => 'Paginas con valor de globo incognite',
	'geodata-unknown-region-category' => 'Paginas con valor de region incognite',
);

/** Indonesian (Bahasa Indonesia)
 * @author Farras
 */
$messages['id'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: lintang salah',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: bujur salah',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: jenis tidak dikenal "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globe tidak dikenal "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format kode wilayah tidak sah',
	'geodata-limit-exceeded' => 'Batas $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|tag|tag}} per halaman telah dilewati',
	'geodata-broken-tags-category' => 'Halaman dengan tag koordinat rusak',
	'geodata-unknown-type-category' => 'Halaman dengan jenis koordinat tidak dikenal',
	'geodata-unknown-globe-category' => 'Halaman dengan nilai globe tidak dikenal',
	'geodata-unknown-region-category' => 'Halaman dengan nilai wilayah tidak dikenal',
);

/** Italian (italiano)
 * @author Darth Kule
 */
$messages['it'] = array(
	'geodata-desc' => 'Aggiunge funzionalità di archiviazione e recupero di coordinate geografiche',
	'geodata-bad-input' => 'Sono stati passati degli argomenti non validi alla funzione <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitudine non valida',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitudine non valida',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tipo "$1" non riconosciuto',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globo "$1" non riconosciuto',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: formato codice regionale non valido',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: non è possibile avere più di un tag principale per pagina',
	'geodata-limit-exceeded' => 'Il limite di $1 {{PLURAL:$1|tag}} <nowiki>{{#coordinates:}}</nowiki> per pagina è stato superato',
	'geodata-broken-tags-category' => 'Pagine con tag coordinate non validi',
	'geodata-unknown-type-category' => 'Pagine con un tipo di coordinate sconosciuto',
	'geodata-unknown-globe-category' => 'Pagine con un valore globo sconosciuto',
	'geodata-unknown-region-category' => 'Pagine con un valore regione non valido',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'geodata-desc' => '緯度経度を保存/取得する機能を追加する',
	'geodata-bad-input' => '<nowiki>{{#coordinates:}}</nowiki> 関数に正しくない引数が渡されました',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: 緯度が正しくありません',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: 経度が正しくありません',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: 地域コードの書式が正しくありません',
);

/** Javanese (Basa Jawa)
 * @author NoiX180
 */
$messages['jv'] = array(
	'geodata-desc' => 'Tambah fungsi panyimpenan lan pambenahan koordinat geografi',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: garis lintang ora sah',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: garis bujur ora sah',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: jinis "$1" ora dikenal',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globe "$1" ora dikenal',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format kodhé dhaèrah ora sah',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: ora bisa luwih saka siji tag utama per kaca',
	'geodata-limit-exceeded' => 'Wates $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|tag|tag}} per kaca wis karanggèh',
	'geodata-broken-tags-category' => 'Kaca mawa tag koordinat cacat',
	'geodata-unknown-type-category' => 'Kaca mawa jinis koordinat ora dingertèni',
	'geodata-unknown-globe-category' => 'Kaca mawa nilé globe ora dingertèni',
	'geodata-unknown-region-category' => 'Kaca mawa nilé dhaèrah ora sah',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: არასწორი განედი',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: არასწორი გრძედი',
	'geodata-bad-type' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: ამოუცნობი ტიპი „$1“',
	'geodata-bad-globe' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: ამოუცნობი მსოფლიო „$1“',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: რეგიონის კოდის არასწორი ფორმატი',
	'geodata-unknown-type-category' => 'გვერდები კოორდინატთა უცნობი ტიპით',
);

/** Korean (한국어)
 * @author 아라
 */
$messages['ko'] = array(
	'geodata-desc' => '지리적 좌표 저장 및 검색 기능 추가',
	'geodata-bad-input' => '<nowiki>{{#coordinates:}}</nowiki> 함수에 잘못된 인수를 전달했습니다',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: 잘못된 위도',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: 잘못된 경도',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: 인식할 수 없는 "$1" 유형',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: 인식할 수 없는 "$1" 세계',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: 잘못된 지역 코드 형식',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: 문서당 기본 태그 하나 이상을 가질 수 없습니다',
	'geodata-limit-exceeded' => '문서당 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|태그}} $1개 제한을 초과했습니다',
	'geodata-broken-tags-category' => '형식이 잘못된 좌표 태그로 된 문서',
	'geodata-unknown-type-category' => '알 수 없는 좌표 유형으로 된 문서',
	'geodata-unknown-globe-category' => '알 수 없는 세계 값으로 된 문서',
	'geodata-unknown-region-category' => '잘못된 지역 값으로 된 문서',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'geodata-desc' => "Brängk Funksjuhne zom Faßhallde un Widerfenge fun Ko'ordenaate om Jloobos en et Wiki.",
	'geodata-bad-input' => 'Onjöltijje Daate sin aan di Funxjuhn <nowiki>{{#coordinates:}}</nowiki> övverjävve woode.',
	'geodata-bad-latitude' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Dat es en onjöltijje Breedte om Jloobos.',
	'geodata-bad-longitude' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Dat es en onjöltije Längde om Jloobos.',
	'geodata-bad-type' => "<code><nowiki>{{#coordinates:}}</nowiki></code>: „$1“ es en onbekannte Zoot Ko'ordenaate.",
	'geodata-bad-globe' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Dä Jlobus „$1“ känne mer nit.',
	'geodata-bad-region' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Dat es en onjölteje Afköözong för de Rejoon.',
	'geodata-multiple-primary' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Mer künne blooß ein vörjetroke Aanjab pro Sigg han.',
	'geodata-limit-exceeded' => 'De Bovverjränz vun {{PLURAL:$1|einem|$1|keinem}} <code><nowiki>{{#coordinates:}}</nowiki></code>-{{PLURAL:$1|Befähl|Befähle|Befähl}} pro Sigg es övverschredde.',
	'geodata-broken-tags-category' => "Sigge met kapodde Befähle för Ko'ordenaate",
	'geodata-unknown-type-category' => "Sigge med ene onbekannte Zoote Ko'ordenaate",
	'geodata-unknown-globe-category' => 'Sigge med enem unbikannte Jlobus',
	'geodata-unknown-region-category' => 'Sigge med ene onjöltijje Rejon',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'geodata-desc' => 'Setzt Fonctionalitéite vum Späicheren an Ofruffe vu geografesche Koordinaten derbäi',
	'geodata-bad-input' => "Et goufen net valabel Argumenter un d'Fonctioun <nowiki>{{#coordinates:}}</nowiki> virugereecht",
	'geodata-bad-type' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: onbekannte Koordinatentyp "$1"',
	'geodata-broken-tags-category' => 'Säite mat Koordinaten, wou e Feeler an de Koordinaten ass',
	'geodata-unknown-type-category' => 'Säite mat engem onbekannten Typ vu Koordinaten',
	'geodata-unknown-globe-category' => 'Säite mat engem onbekannte Wäert fir de Globus',
	'geodata-unknown-region-category' => "Säite mat engem onbekannte Wäert fir d'Regioun",
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'geodata-desc' => 'Додава можност за складирање и повикување на географски координати',
	'geodata-bad-input' => 'На функцијата <nowiki>{{#coordinates:}}</nowiki> ѝ се дадени неважечки аргументи',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: неважечка геог. ширина',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: неважечка геог. должина',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: непрепознаен тип „$1“',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: непрепознаен глобус „$1“',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: неважечки коден формат за регион',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: не може да има повеќе од една главна ознака по страница',
	'geodata-limit-exceeded' => 'Ја надминавте границата од $1 {{PLURAL:$1|ознака|ознаки}} <nowiki>{{#coordinates:}}</nowiki> по страница',
	'geodata-broken-tags-category' => 'Страници со неправилно напишани координатни ознаки',
	'geodata-unknown-type-category' => 'Страници со непознат тип на координати',
	'geodata-unknown-globe-category' => 'Страници со непозната вредност за глобус',
	'geodata-unknown-region-category' => 'Страници со непозната вредност за регион',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 */
$messages['ml'] = array(
	'geodata-desc' => 'ഭൂസ്ഥിരാങ്കാങ്ങൾ ശേഖരിക്കാനും എടുക്കാനുമുള്ള സൗകര്യം കൂട്ടിച്ചേർക്കുന്നു',
	'geodata-bad-input' => '<nowiki>{{#coordinates:}}</nowiki> സൗകര്യത്തിലേയ്ക്ക് അസാധുവായ വിലയാണ് കടത്തിവിട്ടത്',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: അസാധുവായ അക്ഷാംശം',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: അസാധുവായ രേഖാംശം',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: തിരിച്ചറിയാത്ത തരം "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: തിരിച്ചറിയാത്ത ഭൂഗോളമാതൃക "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: അസാധുവായ പ്രദേശ കോഡ് തരം',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: ഒരു താളിൽ ഒന്നിലധികം പ്രാഥമിക ടാഗ് എടുക്കാനാവില്ല',
	'geodata-limit-exceeded' => 'താളിൽ {{PLURAL:$1|ഒരു|$1}} <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|ടാഗ്|ടാഗുകൾ}} എന്ന പരിധി അധികരിച്ചു',
	'geodata-broken-tags-category' => 'തെറ്റായ വിധത്തിലുള്ള സ്ഥിരാങ്ക ടാഗുകൾ ഉള്ള താളുകൾ',
	'geodata-unknown-type-category' => 'അപരിചിതമായ തരത്തിലുള്ള സ്ഥിരാങ്കങ്ങൾ ഉള്ള താളുകൾ',
	'geodata-unknown-globe-category' => 'ഭൂഗോളമാതൃകയുടെ വില അപരിചിതമായ താളുകൾ',
	'geodata-unknown-region-category' => 'പ്രദേശത്തിന്റെ വില അസാധുവായ താളുകൾ',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'geodata-desc' => 'Menambahkan kefungsian storan dan pengambilan koordinat geografi',
	'geodata-bad-input' => 'Hujah-hujah yang tidak sah telah diserahkan kepada fungsi <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: garis lintang tidak sah',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: garis bujur tidak sah',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: jenis "$1" tidak dikenali',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: glob "$1" tidak dikenali',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format kod kawasan tidak sah',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: tidak boleh ada lebih daripada satu teg utama sehalaman',
	'geodata-limit-exceeded' => 'Had $1 <nowiki>{{#coordinates:}}</nowiki> teg sehalaman telah dilampaui',
	'geodata-broken-tags-category' => 'Halaman dengan teg koordinat yang tidak elok',
	'geodata-unknown-type-category' => 'Halaman dengan jenis koordinat yang tidak dikenali',
	'geodata-unknown-globe-category' => 'Halaman dengan nilai glob yang tidak dikenali',
	'geodata-unknown-region-category' => 'Halaman dengan nilai kawasan yang tidak sah',
);

/** Maltese (Malti)
 * @author Chrisportelli
 */
$messages['mt'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitudni ħażina',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: lonġitudni ħażina',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tip "$1" mhux rikonoxxut',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: globu "$1" mhux rikonoxxut',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: format ħażin tal-kodiċi tar-reġjun',
	'geodata-broken-tags-category' => "Paġni b'''tags'' ta' koordinati ħżiena",
	'geodata-unknown-type-category' => "Paġni b'tip ta' koordinati mhux magħrufa",
	'geodata-unknown-globe-category' => "Paġni b'valur mhux magħruf għall-globu",
	'geodata-unknown-region-category' => "Paġni b'valur ħażin għar-reġjun",
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'geodata-desc' => 'Voegt geografische coördinatenopslag en weergavefunctionaliteit toe',
	'geodata-bad-input' => 'Er zijn ongeldige argumenten opgegeven in de functie <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: ongeldige breedtegraad',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: ongeldige lengtegraad',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: type "$1" niet herkend',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: wereldbol "$1" niet herkend',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: ongeldig formaat van de regiocode',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: er kan niet meer dan één primaire tag per pagina zijn',
	'geodata-limit-exceeded' => 'De limiet van $1 <nowiki>{{#coordinates:}}</nowiki>-{{PLURAL:$1|tag|tags}} per pagina is overschreden',
	'geodata-broken-tags-category' => "Pagina's met onjuiste coördinatenlabels",
	'geodata-unknown-type-category' => "Pagina's met onbekend type coördinaten",
	'geodata-unknown-globe-category' => "Pagina's met onbekende waarde voor wereldbol",
	'geodata-unknown-region-category' => "Pagina's met ongeldige waarde voor regio",
);

/** Polish (polski)
 * @author BeginaFelicysym
 */
$messages['pl'] = array(
	'geodata-desc' => 'Dodaje funkcje przechowywania i pobierania współrzędnych geograficznych',
	'geodata-bad-input' => 'Nieprawidłowe argumenty zostały przekazane do funkcji <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: nieprawidłowa szerokość geograficzna',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: nieprawidłowa długość geograficzna',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: nieprawidłowy typ "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: nierozpoznany glob "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: nieprawidłowy format kodu regionu',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: nie można podać więcej niż jednego tagu podstawowego na stronie',
	'geodata-limit-exceeded' => 'Ograniczenie $1 {{PLURAL:$1|znacznika|znaczników|znaczników}} <nowiki>{{#coordinates:}}</nowiki>  na stronie zostało przekroczone',
	'geodata-broken-tags-category' => 'Strony niepoprawnymi znacznikami współrzędnych',
	'geodata-unknown-type-category' => 'Strony ze współrzędnymi nieznanego typu',
	'geodata-unknown-globe-category' => 'Strony z nieznaną wartością globu',
	'geodata-unknown-region-category' => 'Strony z nieprawidłową wartością regionu',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 */
$messages['pms'] = array(
	'geodata-desc' => "A gionta la funsion ëd memorisassion e d'estrassion ëd le coordinà geogràfiche",
	'geodata-bad-input' => "Dj'argoment pa bon a son ëstàit mandà a la funsion <nowiki>{{#coordinates:}}</nowiki>",
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitùdin pa bon-a',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitùdin pa bon-a',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: tipo «$1» nen arconossù',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: glòb «$1» nen arconossù',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: formà dël còdes ëd region pa bon',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: as peul pa avèisse pi che na tichëtta primaria për pàgina',
	'geodata-limit-exceeded' => "El lìmit ëd $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|tichëtta|tichëtte}} për pàgina a l'é stàit passà",
	'geodata-broken-tags-category' => 'Pàgine con dle tichëtte ëd coordinà mal formà',
	'geodata-unknown-type-category' => 'Pàgine con na sòrt ëd coordinà nen conossùa',
	'geodata-unknown-globe-category' => 'Pàgine con un valor ëd glòb nen conossù',
	'geodata-unknown-region-category' => 'Pàgine con un valor ëd region pa bon',
);

/** Portuguese (português)
 * @author Helder.wiki
 * @author Opraco
 */
$messages['pt'] = array(
	'geodata-broken-tags-category' => 'Páginas com coordenadas formatadas incorretamente',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Helder.wiki
 * @author Opraco
 */
$messages['pt-br'] = array(
	'geodata-broken-tags-category' => 'Páginas com coordenadas formatadas incorretamente',
);

/** Romanian (română)
 * @author Firilacroco
 */
$messages['ro'] = array(
	'geodata-primary-coordinate' => 'primar',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: latitudine invalide',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: longitudine invalide',
);

/** Russian (русский)
 * @author David1010
 * @author Eleferen
 * @author Kalan
 * @author Max Semenik
 */
$messages['ru'] = array(
	'geodata-desc' => 'Добавляет возможность хранить и получать географические координаты',
	'geodata-bad-input' => 'В функцию <nowiki>{{#coordinates:}}</nowiki> были переданы некорректные аргументы',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: недопустимая широта',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: недопустимая долгота',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: неопознанный тип «$1»',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: неизвестный глобус «$1»',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: некорректный формат кода региона',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: нельзя иметь более одной первичной метки на странице',
	'geodata-limit-exceeded' => 'Ограничение в $1 {{PLURAL:$1|тег|тега|тегов}} <nowiki>{{#coordinates:}}</nowiki> на страницу было исчерпано',
	'geodata-broken-tags-category' => 'Страницы с некорректными тегами координат',
	'geodata-unknown-type-category' => 'Страницы с неизвестным типом координат',
	'geodata-unknown-globe-category' => 'Страницы с неизвестным глобусом',
	'geodata-unknown-region-category' => 'Страницы с некорректным регионом',
);

/** Sinhala (සිංහල)
 * @author පසිඳු කාවින්ද
 */
$messages['si'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: වලංගු නොවන අක්ෂාංශය',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: වලංගු නොවන දේශාංශය',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: හදුනා නොගත් වර්ගය "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: හදුනා නොගත් වර්තිකාව "$1"',
	'geodata-unknown-globe-category' => 'නොදන්නා ගෝලීය අගයක් සහිත පිටු',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Rancher
 */
$messages['sr-ec'] = array(
	'geodata-unknown-type-category' => 'Странице са непознатом врстом координата',
	'geodata-unknown-globe-category' => 'Странице са непознатом вредности за глобус',
	'geodata-unknown-region-category' => 'Странице са неисправном вредности за регион',
);

/** Serbian (Latin script) (srpski (latinica)‎)
 */
$messages['sr-el'] = array(
	'geodata-unknown-type-category' => 'Stranice sa nepoznatom vrstom koordinata',
	'geodata-unknown-globe-category' => 'Stranice sa nepoznatom vrednosti za globus',
	'geodata-unknown-region-category' => 'Stranice sa neispravnom vrednosti za region',
);

/** Swedish (svenska)
 * @author Ainali
 * @author Boivie
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'geodata-desc' => 'Lägger till funktionalitet för att lagra och hämta geografiska koordinater',
	'geodata-bad-input' => 'Ogiltiga argument har skickats till <nowiki>{{#coordinates:}}</nowiki>funktionen',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: ogiltig latitud',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: ogiltig longitud',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: oigenkänd typ "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: oigenkänd glob "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: ogiltig regionkodsformat',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: kan inte ha mer än en primär tagg per sida',
	'geodata-limit-exceeded' => 'Gränsen på $1 <nowiki>{{#coordinates:}}</nowiki>{{PLURAL:$1|tagg|taggar}} per sida har överskridits',
	'geodata-broken-tags-category' => 'Sidor med felaktiga koordinattaggar',
	'geodata-unknown-type-category' => 'Sidor med okänd typ av koordinater',
	'geodata-unknown-globe-category' => 'Sidor med okänt globvärde',
	'geodata-unknown-region-category' => 'Sidor med ogiltigt regionvärde',
);

/** Tamil (தமிழ்)
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'geodata-primary-coordinate' => 'முதன்மை',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'geodata-desc' => 'Nagdaragdag ng tungkuling pag-iimbak at pagbawi ng mga tugmaang pangheograpiya',
	'geodata-bad-input' => 'Naipasa na ang hindi katanggap-tanggap na mga pangangatwiran papunta sa tungkuling <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: hindi katanggap-tanggap na latitud',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: hindi katanggap-tanggap na longhitud',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: hindi nakikilalang uri na "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: hindi nakikilalang globo na "$1"',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: hindi katanggap-tanggap na anyo ng kodigo ng rehiyon',
	'geodata-multiple-primary' => "<nowiki>{{#coordinates:}}</nowiki>: hindi maaaring magkaroon ng isang pangunahing tatak sa bawa't pahina",
	'geodata-limit-exceeded' => "Nalampasan na ang hangganang $1 <nowiki>{{#coordinates:}}</nowiki> {{PLURAL:$1|tatak|mga tatak}} ng bawa't pahina",
	'geodata-broken-tags-category' => 'Mga pahinang mayroong kapangitan ang mga tatak na pangtugmaan',
	'geodata-unknown-type-category' => 'Mga pahinang mayroong hindi nakikilalang mga uri ng mga tugmaan',
	'geodata-unknown-globe-category' => 'Mga pahinang mayroong hindi nakikilalang halaga ng globo',
	'geodata-unknown-region-category' => 'Mga pahinang may hindi katanggap-tanggap na halaga ng rehiyon',
);

/** Ukrainian (українська)
 * @author Base
 */
$messages['uk'] = array(
	'geodata-desc' => 'Додає можливість зберігати і отримувати географічні координати',
	'geodata-bad-input' => 'Фунцкції <nowiki>{{#coordinates:}}</nowiki> було передано некоретні аргументи',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: недопустима широта',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: недопустима довгота',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: невпізнаний тип «$1»',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: невпізнаний глобус «$1»',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: недопустимий формат коду регіону',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: не можна мати більш ніж одну первинний теґ на сторінці',
	'geodata-limit-exceeded' => 'Було перевершено обмеження в $1 {{PLURAL:$1|теґ|теґи|теґів}} <nowiki>{{#coordinates:}}</nowiki> на сторінку',
	'geodata-broken-tags-category' => 'Сторінки з некоректними теґами координат',
	'geodata-unknown-type-category' => 'Сторінки з невідомим типом координат',
	'geodata-unknown-globe-category' => 'Сторінки з невідомим глобусом',
	'geodata-unknown-region-category' => 'Сторінки з некоректним регіоном',
);

/** Urdu (اردو)
 * @author පසිඳු කාවින්ද
 */
$messages['ur'] = array(
	'geodata-unknown-type-category' => 'نقاط مقام کی نامعلوم قسم کے ساتھ صفحات',
	'geodata-unknown-globe-category' => 'نامعلوم دنیا کی قدر کے ساتھ صفحات',
	'geodata-unknown-region-category' => 'باطل کے علاقے کی قدر کے ساتھ صفحات',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'geodata-desc' => 'Cho phép lưu trữ và truy cập dữ liệu tọa độ',
	'geodata-bad-input' => 'Đã đưa tham số không hợp lệ vào hàm <nowiki>{{#coordinates:}}</nowiki>',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: vĩ độ không hợp lệ',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: vĩ độ không hợp lệ',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: kiểu bất ngờ “$1”',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: địa cầu bất ngờ “$1”',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: định dạng mã khu vực không hợp lệ',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: một trang không có thể chứa hơn một thẻ chính',
	'geodata-limit-exceeded' => 'Đã vượt quá giới hạn mỗi trang có $1 <nowiki>{{#coordinates:}}</nowiki> thẻ',
	'geodata-broken-tags-category' => 'Trang có thẻ tọa độ hỏng',
	'geodata-unknown-type-category' => 'Trang có kiểu tọa độ không rõ',
	'geodata-unknown-globe-category' => 'Trang có giá trị địa cầu không rõ',
	'geodata-unknown-region-category' => 'Trang có giá trị khu vực không hợp lệ',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Liangent
 * @author Shizhao
 */
$messages['zh-hans'] = array(
	'geodata-desc' => '添加地理坐标存储和检索功能',
	'geodata-bad-input' => '无效参数传递至<nowiki>{{#coordinates:}}</nowiki>函数',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>：无效的纬度',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>：无效的经度',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>：未识别类型“$1”',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>：未识别星球“$1”',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>：无效的地区代码格式',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>：每页不能有多个主标签',
	'geodata-limit-exceeded' => '每页的<nowiki>{{#coordinates:}}</nowiki>标签数量已超出$1个的最大限制',
	'geodata-broken-tags-category' => '包含格式不正确的坐标标签的页面',
	'geodata-unknown-type-category' => '包含未知坐标类型的页面',
	'geodata-unknown-globe-category' => '包含未知星球值的页面',
	'geodata-unknown-region-category' => '包含无效地区值的页面',
);
