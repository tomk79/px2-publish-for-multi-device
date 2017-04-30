# tomk79/px2-publish-for-multi-device

複数のデバイス向けのバージョンをパブリッシュできる、 Pickles 2 用のパブリッシュプラグイン。


## 導入手順 - Setup

### 1. composer.json に tomk79/px2-publish-for-multi-device を追加

require の項目に、"tomk79/px2-publish-for-multi-device" を追加します。

```json
{
	"repositories": [
		{
			"type": "git",
			"url": "https://github.com/tomk79/px2-publish-for-multi-device.git"
		}
	],
	"require": {
		"tomk79/px2-publish-for-multi-device": "dev-master"
	},
}
```


追加したら、`composer update` を実行して変更を反映することを忘れずに。

```bash
$ composer update
```


### 2. config.php に、プラグインを設定

設定ファイル config.php (通常は `./px-files/config.php`) を編集します。
`before_content` にある、PX=publish の設定を、次の例を参考に書き換えます。

```php
<?php
	/* 中略 */

	/**
	 * funcs: Before content
	 *
	 * サイトマップ読み込みの後、コンテンツ実行の前に実行するプラグインを設定します。
	 */
	$conf->funcs->before_content = array(
		// PX=api
		'picklesFramework2\commands\api::register' ,

		// PX=publish
		'tomk79\pickles2\publishForMultiDevice\publish::register('.json_encode(array(
			'devices'=>array(
				array(
					'user_agent'=>'iPhone',
					'path_publish_dir'=>'./px-files/dist_smt/',
				),
				array(
					'user_agent'=>'iPad',
					'path_publish_dir'=>'./px-files/dist_tab/',
				),
			)
		)).')' ,
	);
```

Pickles 2 の設定をJSON形式で編集している方は、`config.json` の該当箇所に追加してください。

### 3. パブリッシュを実行

標準的な Pickles 2 のパブリッシュと同じ手順で、パブリッシュコマンドを実行します。

```bash
$ php .px_execute.php /?PX=publish.run
```


## オプション - Options

```php
<?php
	$conf->funcs->before_content = array(
		// PX=api
		'picklesFramework2\commands\api::register' ,

		// PX=publish
		'tomk79\pickles2\publishForMultiDevice\publish::register('.json_encode(array(
			// ↓パブリッシュするデバイスの情報を設定する。
			'devices'=>array(
				array(
					'user_agent'=>'iPhone', // USER_AGENT 文字列
					'path_publish_dir'=>'./px-files/dist_smt/', // このデバイス向けのパブリッシュ先ディレクトリ
				),
				array(
					'user_agent'=>'iPad',
					'path_publish_dir'=>'./px-files/dist_tab/',
				),
				/* ...以下同様... */
			)
		)).')' ,
	);
```


## 更新履歴 - Change log

### tomk79/px2-publish-for-multi-device dev-develop (2017年??月??日)

- First release.


## ライセンス - License

MIT License


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <http://www.pxt.jp/>
- Twitter: @tomk79 <http://twitter.com/tomk79/>
