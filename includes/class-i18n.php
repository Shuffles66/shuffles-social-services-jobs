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
	 * Offered languages: code => endonym. English is the markup default.
	 *
	 * @return array
	 */
	public static function langs() {
		return apply_filters(
			'shuffles_ssj_languages',
			array(
				'en' => 'English',
				'ar' => 'العربية',
				'zh' => '中文',
				'el' => 'Ελληνικά',
				'it' => 'Italiano',
				'id' => 'Bahasa Indonesia',
				'pa' => 'ਪੰਜਾਬੀ',
			)
		);
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
			),
		);
		return apply_filters( 'shuffles_ssj_i18n', $map );
	}
}
