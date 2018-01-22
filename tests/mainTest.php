<?php
/**
 * test for tomk79\px2-publish-for-multi-device
 */
class mainTest extends PHPUnit_Framework_TestCase{
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
	}




	/**
	 * Ping
	 */
	public function testPing(){

		// -------------------
		// api.get.vertion
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/' ,
		] );
		clearstatcache();

		// var_dump($output);
		$this->assertTrue( $this->common_error( $output ) );
		$this->assertTrue( true );

	}//testPing();



	/**
	 * publish
	 */
	public function testPublishMultiDevice(){

		// -------------------
		// Execute Multi Device Publish
		$output = $this->passthru( [
			'php',
			__DIR__.'/testdata/standard/.px_execute.php' ,
			'/?PX=publish.run' ,
		] );
		clearstatcache();

		// var_dump($output);
		$this->assertTrue( $this->common_error( $output ) );

		$this->assertTrue( is_dir( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs1/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs2/' ) );

		$this->assertTrue( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.smt2.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/default_only/default.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/default_only/default.smt2.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs1/index.smt.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs1/default_only/default.smt.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs2/_tab/index.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs2/_tab/default_only/default.html' ) );

		$file = file_get_contents(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.html');
		$this->assertTrue( !!preg_match( '/<p>USER_AGENT: <\/p>/s', $file ) );

		$file = file_get_contents(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.smt2.html');
		$this->assertTrue( !!preg_match( '/<p>USER_AGENT: iPhone2\/PicklesCrawler<\/p>/s', $file ) );

		$file = file_get_contents(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs1/index.smt.html');
		$this->assertTrue( !!preg_match( '/<p>USER_AGENT: iPhone\/PicklesCrawler<\/p>/s', $file ) );

		$file = file_get_contents(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs2/_tab/index.html');
		$this->assertTrue( !!preg_match( '/<p>USER_AGENT: iPad\/PicklesCrawler<\/p>/s', $file ) );

		$this->assertEquals(
			md5_file(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.html'),
			md5_file(__DIR__.'/testdata/standard/px-files/dist/index.html')
		);
		$this->assertEquals(
			md5_file(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs/index.smt2.html'),
			md5_file(__DIR__.'/testdata/standard/px-files/dist/index.smt2.html')
		);
		$this->assertEquals(
			md5_file(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs1/index.smt.html'),
			md5_file(__DIR__.'/testdata/standard/px-files/dist_smt/index.smt.html')
		);
		$this->assertEquals(
			md5_file(__DIR__.'/testdata/standard/px-files/_sys/ram/publish/htdocs2/_tab/index.html'),
			md5_file(__DIR__.'/testdata/standard/px-files/dist_tab/_tab/index.html')
		);

	}//testPublishMultiDevice();





	/**
	 * PHPがエラー吐いてないか確認しておく。
	 */
	private function common_error( $output ){
		if( preg_match('/'.preg_quote('Parse error:', '/').'/si', $output) ){ return false; }
		if( preg_match('/'.preg_quote('Fatal error:', '/').'/si', $output) ){ return false; }
		if( preg_match('/'.preg_quote('Warning:', '/').'/si', $output) ){ return false; }
		if( preg_match('/'.preg_quote('Notice:', '/').'/si', $output) ){ return false; }
		return true;
	}


	/**
	 * コマンドを実行し、標準出力値を返す
	 * @param array $ary_command コマンドのパラメータを要素として持つ配列
	 * @return string コマンドの標準出力値
	 */
	private function passthru( $ary_command ){
		set_time_limit(60*10);
		$cmd = array();
		foreach( $ary_command as $row ){
			$param = escapeshellcmd($row);
			array_push( $cmd, $param );
		}
		$cmd = implode( ' ', $cmd );
		ob_start();
		passthru( $cmd );
		$bin = ob_get_clean();
		set_time_limit(30);
		return $bin;
	}

}
