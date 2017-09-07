# 001

自分が好きなように自分の為に自分で作る日報的な記録ツール

---

[もっと詳しく]  
お仕事で、紙のノートやらオンラインノート系のツールを使って日報を書いていたが、自分が必要な様に作った

## 実装されている機能

* 日別にタスクを登録する
  * タスクが完了していなければ残っていく
* タスクにコメントが書ける
* タスクにスケジュール的な _開始時_ と _終了時_ が設定できる
* スケジュールはicalで出力できる

## セットアップ

```
$ git clone git@github.com:hampom/001.git
$ cd 001
$ npm install -y
$ composer install
$ npm run prod
```

### データベース設定

初期値はSQLite（develop）  
必要に応じてMySQLなどに変更する

#### phinx（マイグレーション）設定

productionの項目を編集

```
$ vi phinx.yml
```

#### アプリケーション設定

```
$ vi public/index.php
```

[cakephp/database](https://github.com/cakephp/database)を参照  
規定値は以下の通り

```php
$container['db'] = function ($c) {
    return new Connection([
        'driver' => '\Cake\Database\Driver\Sqlite',
        'database' => '../sql/task.db'
    ]);
};
```

#### マイグレーション

<pre>
$ vendor/bin/phinx migrate -e production
</pre>

Apacheのドキュメントルートを`public`に設定し、`AllowOverride all`を忘れずに

## アクセス

http://[設置したホスト]/YYYY/MM/DD

例）　2010年01月23日なら  
http://[設置したホスト]/2010/01/23
