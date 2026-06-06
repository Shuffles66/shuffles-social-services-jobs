<?php
/**
 * Interface translation (CALD). Browser-side live switching via data-i18n; $0 to run.
 * Strings are auto-translated and PENDING NATIVE REVIEW. Extend/override via the
 * `shuffles_ssj_i18n` and `shuffles_ssj_languages` filters.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_I18n {

	/**
	 * The built-in languages shipped with the plugin: code => endonym.
	 *
	 * @return array
	 */
	public static function builtin_langs() {
		return array(
			'en' => 'English',
			'ar' => 'العربية',
			'zh' => '中文',
			'el' => 'Ελληνικά',
			'it' => 'Italiano',
			'id' => 'Bahasa Indonesia',
			'pa' => 'ਪੰਜਾਬੀ',
		);
	}

	/**
	 * Per-site custom languages, parsed from the `cald_custom_langs` setting.
	 * One per line: "code | Endonym | rtl"  (the rtl flag is optional).
	 *
	 * @return array { 'langs' => array code=>endonym, 'rtl' => array code=>1 }
	 */
	public static function custom_langs() {
		$langs = array();
		$rtl   = array();
		$raw   = (string) ( new Shuffles_SSJ_Settings() )->get( 'cald_custom_langs', '' );
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$p    = array_map( 'trim', explode( '|', $line ) );
			$code = sanitize_key( isset( $p[0] ) ? $p[0] : '' );
			if ( '' === $code ) {
				continue;
			}
			$langs[ $code ] = sanitize_text_field( ( isset( $p[1] ) && '' !== $p[1] ) ? $p[1] : $code );
			if ( isset( $p[2] ) && 'rtl' === strtolower( trim( $p[2] ) ) ) {
				$rtl[ $code ] = 1;
			}
		}
		return array( 'langs' => $langs, 'rtl' => $rtl );
	}

	/**
	 * Offered languages for THIS site: built-ins + per-site custom, narrowed to the
	 * admin-enabled subset (`cald_languages`, comma/space separated). English is always
	 * offered as the markup default.
	 *
	 * @return array code => endonym
	 */
	public static function langs() {
		$custom = self::custom_langs();
		$all    = array_merge( self::builtin_langs(), $custom['langs'] );

		$enabled_raw = trim( (string) ( new Shuffles_SSJ_Settings() )->get( 'cald_languages', '' ) );
		if ( '' !== $enabled_raw ) {
			$codes = array_filter( array_map( 'sanitize_key', preg_split( '/[\s,]+/', $enabled_raw ) ) );
			$codes[] = 'en'; // English never disappears.
			$all   = array_intersect_key( $all, array_flip( $codes ) );
			if ( ! isset( $all['en'] ) ) {
				$all = array_merge( array( 'en' => 'English' ), $all );
			}
		}

		return apply_filters( 'shuffles_ssj_languages', $all );
	}

	/**
	 * Right-to-left language codes (built-in Arabic + any custom-flagged `rtl`).
	 *
	 * @return array code => 1
	 */
	public static function rtl_langs() {
		$rtl    = array( 'ar' => 1 );
		$custom = self::custom_langs();
		foreach ( $custom['rtl'] as $code => $v ) {
			$rtl[ $code ] = 1;
		}
		return apply_filters( 'shuffles_ssj_rtl_languages', $rtl );
	}

	/**
	 * Translation map: lang => [ key => string ]. Keys match `data-i18n` attributes.
	 *
	 * @return array
	 */
	public static function map() {
		$map = array(
			'ar' => array(
				'a11y_contrast' => 'تباين عالٍ',
				'a11y_mono'     => 'بدون ألوان',
				'a11y_easyread' => 'قراءة سهلة',
				'a11y_reset'    => 'إعادة تعيين',
				'filter'        => 'تصفية',
				'apply'         => 'قدّم الآن',
				'respond'       => 'استجب',
				'view_job'      => 'عرض الوظيفة',
				'view_profile'  => 'عرض الملف',
				'post_job'      => 'نشر وظيفة',
				'submit_request' => 'إرسال الطلب',
				'featured'      => '★ مميّزة',
				'd_workers'         => 'العاملون المتاحون',
				'd_orgs'            => 'المنظمات',
				'avail_now'         => 'متاح الآن',
				'within'            => 'ضمن',
				'any'               => 'أي',
				'all_services'      => 'كل الخدمات',
				'all_sectors'       => 'كل القطاعات',
				'all_funding'       => 'كل مصادر التمويل',
				'open_only'         => 'فقط مع شواغر مفتوحة',
				'view_profile_jobs' => 'عرض الملف والوظائف',
				'ph_workers'        => 'ابحث عن عاملين…',
				'ph_near'           => 'بالقرب من ضاحية…',
			),
			'zh' => array(
				'a11y_contrast' => '高对比度',
				'a11y_mono'     => '无颜色',
				'a11y_easyread' => '易读',
				'a11y_reset'    => '重置',
				'filter'        => '筛选',
				'apply'         => '立即申请',
				'respond'       => '回应',
				'view_job'      => '查看职位',
				'view_profile'  => '查看资料',
				'post_job'      => '发布职位',
				'submit_request' => '提交请求',
				'featured'      => '★ 精选',
				'd_workers'         => '可用工作者',
				'd_orgs'            => '组织',
				'avail_now'         => '现在有空',
				'within'            => '范围内',
				'any'               => '任意',
				'all_services'      => '所有服务',
				'all_sectors'       => '所有领域',
				'all_funding'       => '所有资助',
				'open_only'         => '仅有空缺职位',
				'view_profile_jobs' => '查看资料和职位',
				'ph_workers'        => '搜索工作者…',
				'ph_near'           => '附近的城区…',
			),
			'el' => array(
				'a11y_contrast' => 'Υψηλή αντίθεση',
				'a11y_mono'     => 'Χωρίς χρώμα',
				'a11y_easyread' => 'Εύκολη ανάγνωση',
				'a11y_reset'    => 'Επαναφορά',
				'filter'        => 'Φίλτρο',
				'apply'         => 'Υποβολή τώρα',
				'respond'       => 'Απάντηση',
				'view_job'      => 'Προβολή θέσης',
				'view_profile'  => 'Προβολή προφίλ',
				'post_job'      => 'Δημοσίευση θέσης',
				'submit_request' => 'Υποβολή αιτήματος',
				'featured'      => '★ Προβεβλημένη',
				'd_workers'         => 'Διαθέσιμοι εργαζόμενοι',
				'd_orgs'            => 'Οργανισμοί',
				'avail_now'         => 'Διαθέσιμος τώρα',
				'within'            => 'Εντός',
				'any'               => 'Οποιαδήποτε',
				'all_services'      => 'Όλες οι υπηρεσίες',
				'all_sectors'       => 'Όλοι οι τομείς',
				'all_funding'       => 'Όλη η χρηματοδότηση',
				'open_only'         => 'Μόνο με ανοιχτές θέσεις',
				'view_profile_jobs' => 'Προβολή προφίλ & θέσεων',
				'ph_workers'        => 'Αναζήτηση εργαζομένων…',
				'ph_near'           => 'Κοντά σε προάστιο…',
			),
			'it' => array(
				'a11y_contrast' => 'Alto contrasto',
				'a11y_mono'     => 'Senza colore',
				'a11y_easyread' => 'Lettura facile',
				'a11y_reset'    => 'Reimposta',
				'filter'        => 'Filtra',
				'apply'         => 'Candidati ora',
				'respond'       => 'Rispondi',
				'view_job'      => 'Vedi offerta',
				'view_profile'  => 'Vedi profilo',
				'post_job'      => 'Pubblica offerta',
				'submit_request' => 'Invia richiesta',
				'featured'      => '★ In evidenza',
				'd_workers'         => 'Operatori disponibili',
				'd_orgs'            => 'Organizzazioni',
				'avail_now'         => 'Disponibile ora',
				'within'            => 'Entro',
				'any'               => 'Qualsiasi',
				'all_services'      => 'Tutti i servizi',
				'all_sectors'       => 'Tutti i settori',
				'all_funding'       => 'Tutti i finanziamenti',
				'open_only'         => 'Solo con posizioni aperte',
				'view_profile_jobs' => 'Vedi profilo e offerte',
				'ph_workers'        => 'Cerca operatori…',
				'ph_near'           => 'Vicino a un sobborgo…',
			),
			'id' => array(
				'a11y_contrast' => 'Kontras tinggi',
				'a11y_mono'     => 'Tanpa warna',
				'a11y_easyread' => 'Mudah dibaca',
				'a11y_reset'    => 'Atur ulang',
				'filter'        => 'Saring',
				'apply'         => 'Lamar sekarang',
				'respond'       => 'Tanggapi',
				'view_job'      => 'Lihat lowongan',
				'view_profile'  => 'Lihat profil',
				'post_job'      => 'Pasang lowongan',
				'submit_request' => 'Kirim permintaan',
				'featured'      => '★ Unggulan',
				'd_workers'         => 'Pekerja tersedia',
				'd_orgs'            => 'Organisasi',
				'avail_now'         => 'Tersedia sekarang',
				'within'            => 'Dalam',
				'any'               => 'Semua',
				'all_services'      => 'Semua layanan',
				'all_sectors'       => 'Semua sektor',
				'all_funding'       => 'Semua pendanaan',
				'open_only'         => 'Hanya dengan lowongan terbuka',
				'view_profile_jobs' => 'Lihat profil & lowongan',
				'ph_workers'        => 'Cari pekerja…',
				'ph_near'           => 'Dekat suatu daerah…',
			),
			'pa' => array(
				'a11y_contrast' => 'ਉੱਚ ਕੰਟ੍ਰਾਸਟ',
				'a11y_mono'     => 'ਰੰਗ ਤੋਂ ਬਿਨਾਂ',
				'a11y_easyread' => 'ਸੌਖਾ ਪੜ੍ਹਨ',
				'a11y_reset'    => 'ਮੁੜ-ਸੈੱਟ',
				'filter'        => 'ਫਿਲਟਰ',
				'apply'         => 'ਹੁਣੇ ਅਰਜ਼ੀ ਦਿਓ',
				'respond'       => 'ਜਵਾਬ ਦਿਓ',
				'view_job'      => 'ਨੌਕਰੀ ਵੇਖੋ',
				'view_profile'  => 'ਪ੍ਰੋਫਾਈਲ ਵੇਖੋ',
				'post_job'      => 'ਨੌਕਰੀ ਪੋਸਟ ਕਰੋ',
				'submit_request' => 'ਬੇਨਤੀ ਭੇਜੋ',
				'featured'      => '★ ਵਿਸ਼ੇਸ਼',
				'd_workers'         => 'ਉਪਲਬਧ ਕਾਮੇ',
				'd_orgs'            => 'ਸੰਸਥਾਵਾਂ',
				'avail_now'         => 'ਹੁਣ ਉਪਲਬਧ',
				'within'            => 'ਅੰਦਰ',
				'any'               => 'ਕੋਈ ਵੀ',
				'all_services'      => 'ਸਾਰੀਆਂ ਸੇਵਾਵਾਂ',
				'all_sectors'       => 'ਸਾਰੇ ਖੇਤਰ',
				'all_funding'       => 'ਸਾਰੀ ਫੰਡਿੰਗ',
				'open_only'         => 'ਸਿਰਫ਼ ਖੁੱਲ੍ਹੀਆਂ ਅਸਾਮੀਆਂ ਨਾਲ',
				'view_profile_jobs' => 'ਪ੍ਰੋਫਾਈਲ ਤੇ ਨੌਕਰੀਆਂ ਵੇਖੋ',
				'ph_workers'        => 'ਕਾਮੇ ਖੋਜੋ…',
				'ph_near'           => 'ਕਿਸੇ ਉਪਨਗਰ ਦੇ ਨੇੜੇ…',
			),
		);

		// Per-site translation overrides / new-language strings, pasted as JSON in the CALD tab:
		// { "vi": { "filter": "Lọc", "view_job": "Xem việc làm" }, ... }
		$raw = trim( (string) ( new Shuffles_SSJ_Settings() )->get( 'cald_lang_overrides', '' ) );
		if ( '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $code => $dict ) {
					$code = sanitize_key( $code );
					if ( '' === $code || ! is_array( $dict ) ) {
						continue;
					}
					$clean = array();
					foreach ( $dict as $k => $v ) {
						$clean[ sanitize_key( $k ) ] = sanitize_text_field( (string) $v );
					}
					$map[ $code ] = isset( $map[ $code ] ) ? array_merge( $map[ $code ], $clean ) : $clean;
				}
			}
		}

		return apply_filters( 'shuffles_ssj_i18n', $map );
	}
}
