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
	'geodata-primary-coordinate' => 'primary',
);

/** Message documentation (Message documentation)
 * @author Max Semenik
 * @author SPQRobin
 */
$messages['qqq'] = array(
	'geodata-desc' => '{{desc}}',
	'geodata-bad-globe' => 'Terrestrial body on which the coordinate resides. By default, Earth is assumed. Other globes include earth, mercury, venus, moon, mars, ...',
	'geodata-limit-exceeded' => '$1 is a number',
	'geodata-broken-tags-category' => 'Name of the tracking category',
	'geodata-unknown-type-category' => 'Name of the tracking category',
	'geodata-unknown-globe-category' => 'Name of the tracking category',
	'geodata-unknown-region-category' => 'Name of the tracking category',
	'geodata-primary-coordinate' => "Localised name of parameter that makes <nowiki>{{#coordinates:}}</nowiki> tag primary (i.e. containing the primary coordinates of the page's subject)",
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
	'geodata-primary-coordinate' => 'primaria',
);

/** Belarusian (Taraškievica orthography) (‪беларуская (тарашкевіца)‬)
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
	'geodata-primary-coordinate' => 'першасная',
);

/** Breton (brezhoneg)
 * @author Y-M D
 */
$messages['br'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki> : ledred direizh',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki> : hedred direizh',
);

/** Czech (česky)
 * @author Jkjk
 */
$messages['cs'] = array(
	'geodata-desc' => 'Přidá funkci samostatného uchovávání a vyhledávání zeměpisných souřadnic',
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
	'geodata-primary-coordinate' => 'hlavní',
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
	'geodata-primary-coordinate' => 'primäre',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'geodata-primary-coordinate' => 'Sıfteyên',
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
	'geodata-primary-coordinate' => 'primaria',
);

/** Persian (فارسی)
 * @author جواد
 */
$messages['fa'] = array(
	'geodata-primary-coordinate' => 'اولیه',
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
	'geodata-primary-coordinate' => 'primaire',
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
	'geodata-primary-coordinate' => 'primaria',
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
	'geodata-broken-tags-category' => 'דפים על תגי נקודות ציון בלתי־תקינים',
	'geodata-unknown-type-category' => 'דפים עם סוג לא ידוע של נקודות ציון',
	'geodata-unknown-globe-category' => 'דפים עם ערך גלובוס לא ידוע',
	'geodata-unknown-region-category' => 'דפים עם ערך אזור לא ידוע',
	'geodata-primary-coordinate' => 'ראשי',
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
	'geodata-primary-coordinate' => 'primarne',
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
	'geodata-primary-coordinate' => 'elsődleges',
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
	'geodata-primary-coordinate' => 'primari',
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
	'geodata-primary-coordinate' => 'principale',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'geodata-desc' => '地理的な緯度経度を格納/取得する機能を追加する',
	'geodata-bad-input' => '<nowiki>{{#coordinates:}}</nowiki> 関数に正しくない引数が渡されました',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>：緯度が正しくありません',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>：経度が正しくありません',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>：地域コードの書式が正しくありません',
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
	'geodata-primary-coordinate' => 'utama',
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
	'geodata-primary-coordinate' => 'მთავარი',
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
	'geodata-primary-coordinate' => '기본',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'geodata-bad-input' => 'Onjöltijje Daate sin aan di Funxjuhn <nowiki>{{#coordinates:}}</nowiki> övverjävve woode.',
	'geodata-bad-latitude' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: onjöltijje Längde om Jloobus',
	'geodata-bad-globe' => '<code><nowiki>{{#coordinates:}}</nowiki></code>: Dä Jlobus „$1“ känne mer nit.',
	'geodata-unknown-globe-category' => 'Sigge med enem unbikannte Jlobus',
	'geodata-unknown-region-category' => 'Sigge med en onjöltijje Rejon',
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
	'geodata-primary-coordinate' => 'primaire',
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
	'geodata-primary-coordinate' => 'главна',
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
	'geodata-primary-coordinate' => 'primer',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'geodata-desc' => 'Voegt geografische coördinatenopslag en weergavefunctionaliteit toe',
	'geodata-bad-input' => 'Er zijn ongeldige argumenten in de <nowiki>{{#coordinates:}}</nowiki>-functie opgegeven',
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
	'geodata-primary-coordinate' => 'primair',
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
	'geodata-primary-coordinate' => 'podstawowy',
);

/** Romanian (română)
 * @author Firilacroco
 */
$messages['ro'] = array(
	'geodata-primary-coordinate' => 'primar',
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
	'geodata-primary-coordinate' => 'Основная',
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
	'geodata-primary-coordinate' => 'ප්‍රාථමික',
);

/** Serbian (Cyrillic script) (‪српски (ћирилица)‬)
 * @author Rancher
 */
$messages['sr-ec'] = array(
	'geodata-unknown-type-category' => 'Странице са непознатом врстом координата',
	'geodata-unknown-globe-category' => 'Странице са непознатом вредности за глобус',
	'geodata-unknown-region-category' => 'Странице са неисправном вредности за регион',
	'geodata-primary-coordinate' => 'главна',
);

/** Swedish (svenska)
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: ogiltig latitud',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: ogiltig longitud',
	'geodata-bad-type' => '<nowiki>{{#coordinates:}}</nowiki>: oigenkänd typ "$1"',
	'geodata-bad-globe' => '<nowiki>{{#coordinates:}}</nowiki>: oigenkänd glob "$1"',
	'geodata-unknown-type-category' => 'Sidor med okänd typ av koordinater',
	'geodata-unknown-globe-category' => 'Sidor med okänt globvärde',
	'geodata-unknown-region-category' => 'Sidor med ogiltigt regionvärde',
	'geodata-primary-coordinate' => 'primär',
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
	'geodata-primary-coordinate' => 'pangunahin',
);

/** Urdu (اردو)
 * @author පසිඳු කාවින්ද
 */
$messages['ur'] = array(
	'geodata-unknown-type-category' => 'نقاط مقام کی نامعلوم قسم کے ساتھ صفحات',
	'geodata-unknown-globe-category' => 'نامعلوم دنیا کی قدر کے ساتھ صفحات',
	'geodata-unknown-region-category' => 'باطل کے علاقے کی قدر کے ساتھ صفحات',
	'geodata-primary-coordinate' => 'بنیادی',
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
	'geodata-primary-coordinate' => 'chính',
);

