<?php
/**
 * PX Commands "publish"
 */
namespace tomk79\pickles2\publishForMultiDevice;

/**
 * PX Commands "publish" path rewriter
 */
class path_rewriter{

	/** Picklesオブジェクト */
	private $px;

	/** プラグイン設定 */
	private $plugin_conf;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param object $json プラグイン設定
	 */
	public function __construct( $px, $json ){
		$this->px = $px;
		$this->plugin_conf = $json;
	}

	/**
	 * パス変換ロジックを正規化する
	 *
	 * @param mixed $callback パス変換ロジック
	 * @return callback 正規化されたパス変換ロジック
	 */
	public function normalize_callback($callback){
		if( is_callable($callback) ){
			// コールバック関数が設定された場合
			return $callback;
		}
		if( is_string($callback) && strpos(trim($callback), 'function') === 0 ){
			// function で始まる文字列が設定された場合
			return eval('return '.$this->conf->path_files.';');
		}
		return $callback;
	}//normalize_callback()

}
