# Exment 一括テスト実施

Exment本体の自動テストを、一括で実行します。


## 実行方法

- srcフォルダの「.env」を、必要に応じて修正します。

- 以下のコマンドを実行してください。

```
# Dockerfile作成
php build.php --test=1

# 全イメージ作成(非同期で実行されます)
php all-build-image.php 

# テスト実行＆コンテナ終了(非同期で実行されます)
php call-test.php

# コンテナ終了(非同期で実行されます)
php all-down.php

# ログファイル収集(テスト実行後に行ってください)
php get-logs.php
```

- 実行完了後、「logs/(実行日時)/(PHPバージョンとデータベース)_(ログファイル)」が作成されますので、内容をご確認ください。  
例1： logs/20201229154300/test_php72_mysql.log  
例2： logs/20201229154300/laravel_php73_mariadb.log  