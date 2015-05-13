<?php
/**
 * Il file base di configurazione di WordPress.
 *
 * Questo file definisce le seguenti configurazioni: impostazioni MySQL,
 * Prefisso Tabella, Chiavi Segrete, Lingua di WordPress e ABSPATH.
 * E' possibile trovare ultetriori informazioni visitando la pagina: del
 * Codex {@link http://codex.wordpress.org/Editing_wp-config.php
 * Editing wp-config.php}. E' possibile ottenere le impostazioni per
 * MySQL dal proprio fornitore di hosting.
 *
 * Questo file viene utilizzato, durante l'installazione, dallo script
 * di creazione di wp-config.php. Non è necessario utilizzarlo solo via
 * web,è anche possibile copiare questo file in "wp-config.php" e
 * rimepire i valori corretti.
 *
 * @package WordPress
 */

// ** Impostazioni MySQL - E? possibile ottenere questoe informazioni
// ** dal proprio fornitore di hosting ** //
/** Il nome del database di WordPress */



define('DB_NAME', 'agritur__renetta_it_wordpress');

/** Nome utente del database MySQL */
define('DB_USER', 'GM36026_agritur');

/** Password del database MySQL */
define('DB_PASSWORD', 'a4gr1tur');

/** Hostname MySQL  */
define('DB_HOST', 'hostingmysql320.register.it');

/** Charset del Database da utilizare nella creazione delle tabelle. */
define('DB_CHARSET', 'utf8');

/** Il tipo di Collazione del Database. Da non modificare se non si ha
idea di cosa sia. */
define('DB_COLLATE', '');

/**#@+
 * Chiavi Univoche di Autenticazione e di Salatura.
 *
 * Modificarle con frasi univoche differenti!
 * E' possibile generare tali chiavi utilizzando {@link https://api.wordpress.org/secret-key/1.1/salt/ servizio di chiavi-segrete di WordPress.org}
 * E' possibile cambiare queste chiavi in qualsiasi momento, per invalidare tuttii cookie esistenti. Ciò forzerà tutti gli utenti ad effettuare nuovamente il login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'RjnF[ rZH%q?FV/`Nfn:6*U9Qup!|{nbpwk+;6Bi&%1[;kz|~z#)8|.KX?~jUK|3');
define('SECURE_AUTH_KEY',  '+?7@@;4`;NALOxV(MwQmld|)unkJ0gx}-T}I8|$Sr0vT(WD%pncS?HF3la}R(ijI');
define('LOGGED_IN_KEY',    'L3~DT~nX{z9/!%*qnCE|wL6Yxw`B($3AgE^kRYJM~BCiMMsie}Nw2xpz(r<*/+&q');
define('NONCE_KEY',        'h+fz=R:(US^2)-Lyr`U%c+Y_bNz{?u;#;@Xh)Z<B $$P%E)h/_jO|-%#VQcM-#jf');
define('AUTH_SALT',        'k-(}P t7!LJZ>`|.DC=Q>m=Z_dKC.+H5/>Y&Bu81sPL4j))o2$*W-o@H)B#-].Il');
define('SECURE_AUTH_SALT', 'Q:j|1k.FC;M}ZJG{]-^#R[enpC&NV0~bps@J%|r[dyHe+v?vH=OgW}~QT||q0Wd_');
define('LOGGED_IN_SALT',   'QY~d,1D@2]A+%2uaY!8Cg|>RiFp8@E2EX1rHt@9_dajc?& I@5H@|Zp77Y1.UY[H');
define('NONCE_SALT',       'cx0mIrL &9qG+-qmHeiXS3z.k#=@KCVqFxR11q0RAc])eO~nCfn,%Xgwau?5*kwf');

/**#@-*/

/**
 * Prefisso Tabella del Database WordPress .
 *
 * E' possibile avere installazioni multiple su di un unico database if you give each a unique
 * fornendo a ciascuna installazione un prefisso univoco.
 * Solo numeri, lettere e sottolineatura!
 */
$table_prefix  = 'wp_';

/**
 * Per gli sviluppatori: modalità di debug di WordPress.
 *
 * Modificare questa voce a TRUE per abilitare la visualizzazione degli avvisi
 * durante lo sviluppo.
 * E' fortemente raccomandato agli svilupaptori di temi e plugin di utilizare
 * WP_DEBUG all'interno dei loro ambienti di sviluppo.
 */
 // Enable WP_DEBUG mode
define('WP_DEBUG', false);

// Enable Debug logging to the /wp-content/debug.log file
define('WP_DEBUG_LOG', false);

// Disable display of errors and warnings 
define('WP_DEBUG_DISPLAY', false);

/* Finito, interrompere le modifiche! Buon blogging. */

/** Path assoluto alla directory di WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Imposta lle variabili di WordPress ed include i file. */
require_once(ABSPATH . 'wp-settings.php');
