<?php
/**
 * PX Commands "publish" path resolver
 */
namespace tomk79\pickles2\publishForMultiDevice;

/**
 * PX Commands "publish" path resolver
 */
class path_resolver{

	/** Picklesオブジェクト */
	private $px;

	/** プラグイン設定 */
	private $plugin_conf;

	/** パス変換オブジェクト */
	private $path_rewriter;

	/** 端末設定 */
	private $device_info;

	/** 対象ファイルのパス情報 */
	private $path_original, $path_rewrited;


	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param object $json プラグイン設定
	 * @param object $path_rewriter パス変換オブジェクト
	 * @param object $device_info 端末設定
	 * @param string $path_original 変換前のパス
	 * @param string $path_rewrited 変換後のパス
	 */
	public function __construct( $px, $json, $path_rewriter, $device_info, $path_original, $path_rewrited ){
		$this->px = $px;
		$this->plugin_conf = $json;
		$this->path_rewriter = $path_rewriter;
		$this->device_info = $device_info;
		$this->path_original = $path_original;
		$this->path_rewrited = $path_rewrited;
	}

	/**
	 * Resolve path
	 * @param  string $src      ソース全体
	 * @return string           変換後のソース全体
	 */
	public function resolve($src){

		$ext = $this->px->fs()->get_extension($this->path_original);

		switch( strtolower($ext) ){
			case 'html':
			case 'htm':
				$src = $this->path_resolve_in_html($src);
				break;
			case 'css':
				$src = $this->path_resolve_in_css($src);
				break;
		}

		return $src;
	}

	/**
	 * HTMLファイル中のパスを解決
	 */
	private function path_resolve_in_html( $src ){

		// HTMLをパース
		$html = str_get_html(
			$src ,
			false, // $lowercase
			false, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);

		if($html === false){
			// HTMLパースに失敗した場合、無加工のまま返す。
			$this->px->error('HTML Parse ERROR. $src size '.strlen($src).' byte(s) given; '.__FILE__.' ('.__LINE__.')');
			return $src;
		}

		$conf_dom_selectors = array(
			'*[href]'=>'href',
			'*[src]'=>'src',
			'form[action]'=>'action',
		);

		foreach( $conf_dom_selectors as $selector=>$attr_name ){
			$ret = $html->find($selector);
			foreach( $ret as $retRow ){
				$val = $retRow->getAttribute($attr_name);
				$val = $this->get_new_path($val);
				$retRow->setAttribute($attr_name, $val);
			}
		}

		$ret = $html->find('*[style]');
		foreach( $ret as $retRow ){
			$val = $retRow->getAttribute('style');
			$val = str_replace('&quot;', '"', $val);
			$val = str_replace('&lt;', '<', $val);
			$val = str_replace('&gt;', '>', $val);
			$val = $this->path_resolve_in_css($val);
			$val = str_replace('"', '&quot;', $val);
			$val = str_replace('<', '&lt;', $val);
			$val = str_replace('>', '&gt;', $val);
			$retRow->setAttribute('style', $val);
		}

		$ret = $html->find('style');
		foreach( $ret as $retRow ){
			$val = $retRow->innertext;
			$val = $this->path_resolve_in_css($val);
			$retRow->innertext = $val;
		}

		$src = $html->outertext;

		return $src;
	}

	/**
	 * CSSファイル中のパスを解決
	 */
	private function path_resolve_in_css( $bin ){

		$rtn = '';

		// url()
		while( 1 ){
			if( !preg_match( '/^(.*?)url\s*\\((.*?)\\)(.*)$/si', $bin, $matched ) ){
				$rtn .= $bin;
				break;
			}
			$rtn .= $matched[1];
			$rtn .= 'url("';
			$res = trim( $matched[2] );
			if( preg_match( '/^(\"|\')(.*)\1$/si', $res, $matched2 ) ){
				$res = trim( $matched2[2] );
			}
			$res = $this->get_new_path( $res );
			$rtn .= $res;
			$rtn .= '")';
			$bin = $matched[3];
		}

		// @import
		$bin = $rtn;
		$rtn = '';
		while( 1 ){
			if( !preg_match( '/^(.*?)@import\s*([^\s\;]*)(.*)$/si', $bin, $matched ) ){
				$rtn .= $bin;
				break;
			}
			$rtn .= $matched[1];
			$rtn .= '@import ';
			$res = trim( $matched[2] );
			if( !preg_match('/^url\s*\(/', $res) ){
				$rtn .= '"';
				if( preg_match( '/^(\"|\')(.*)\1$/si', $res, $matched2 ) ){
					$res = trim( $matched2[2] );
				}
				$res = $this->get_new_path( $res );
				$rtn .= $res;
				$rtn .= '"';
			}else{
				$rtn .= $res;
			}
			$bin = $matched[3];
		}

		return $rtn;
	}

	/**
	 * 変換後の新しいパスを取得
	 */
	private function get_new_path( $path ){
		if( preg_match( '/^(?:[a-zA-Z0-9]+\:|\/\/|\#)/', $path ) ){
			return $path;
		}

		$params = '';
		if( preg_match( '/^(.*?)([\?\#].*)$/', $path, $matched ) ){
			$path = $matched[1];
			$params = $matched[2];
		}

		$rewrite_direction = @$this->device_info->rewrite_direction;
		@preg_match('/^(.*)2(.*)$/', $rewrite_direction, $matched);
		$rewrite_from = @$matched[1];
		$rewrite_to   = @$matched[2];
		if( !strlen($rewrite_from) ){
			$rewrite_from = 'rewrited';
		}
		if( !strlen($rewrite_to) ){
			$rewrite_to = 'origin';
		}

		$type = 'relative';
		if( preg_match('/^\//', $path) ){
			$type = 'absolute';
		}elseif( preg_match('/^\.\//', $path) ){
			$type = 'relative_dotslash';
		}
		$is_slash_closed = false;
		if( preg_match('/\/$/', $path) ){
			$is_slash_closed = true;
			$path .= $this->px->get_directory_index_primary();
		}

		// ------------------

		$cd_origin = $this->px->fs()->get_realpath( $this->path_original );
		$cd_origin = preg_replace( '/^(.*)(\/.*?)$/si', '$1', $cd_origin );
		if( !strlen($cd_origin) ){
			$cd_origin = '/';
		}

		$cd_rewrited = $this->px->fs()->get_realpath( $this->path_rewrited );
		$cd_rewrited = preg_replace( '/^(.*)(\/.*?)$/si', '$1', $cd_rewrited );
		if( !strlen($cd_rewrited) ){
			$cd_rewrited = '/';
		}

		// ------------------

		$realpath_from = $cd_origin;
		$realpath_to = $this->px->fs()->normalize_path($this->px->fs()->get_realpath($path, $cd_origin));

		if( $rewrite_from == 'rewrited' ){
			$realpath_from = $cd_rewrited;
		}
		if( $rewrite_to == 'rewrited' ){
			$realpath_to = $this->path_rewriter->rewrite($realpath_to, $this->device_info->path_rewrite_rule);
			$realpath_to = $this->px->fs()->normalize_path($this->px->fs()->get_realpath($realpath_to, $cd_origin));
		}

		// ------------------

		if( $type == 'relative' || $type == 'relative_dotslash' ){
			$realpath_to = $this->px->fs()->get_relatedpath($realpath_to, $realpath_from);
			if( $type == 'relative' ){
				$realpath_to = preg_replace( '/^\.\//si', '', $realpath_to );
			}elseif( $type == 'relative_dotslash' ){
				$realpath_to = preg_replace( '/^(\.\/)?/si', './', $realpath_to );
			}
		}

		$realpath_to = $this->px->fs()->normalize_path($realpath_to);
		if( $is_slash_closed ){
			$realpath_to = preg_replace( '/'.$this->px->get_directory_index_preg_pattern().'$/', '', $realpath_to );
		}
		$realpath_to .= $params;

		return $realpath_to;
	}

}
