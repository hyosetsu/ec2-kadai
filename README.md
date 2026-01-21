# 実装手順
## EC2インスタンスに接続
```
ssh ec2-user@{IPアドレス} -i C:\Users\ktc\Desktop\{秘密鍵ファイルのパス}
```
とpowershellに入れる
```
Last login: Wed Sep  3 00:30:32 2025 from 160.86.244.53
[ec2-user@ip-172-31-28-24 ~]$
```
と表示されて接続完了

- EC2インスタンスにvimをインストール
```
sudo yum install vim -y
```
でインストール

- screenをインストール
```
sudo yum install screen -y
```
でインストール

- Docker系のインストール
```
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
```

dockerをインストールしdockerグループに追加

usermodを反映するために一度ログアウトする

- Docker Composeのインストール
```
sudo mkdir -p /usr/local/lib/docker/cli-plugins/
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```
インストールできたかの確認
```
docker compose version
```

## 準備はできたので構築していく
### 配信するファイルを置くディレクトリを作成
```
mkdir public
mkdir public/setting
```

### 設定ファイルを作る
- compose.ymlはhttps://github.com/hyosetsu/ec2-kadai/blob/main/compose.yml から
- Dockerfileはhttps://github.com/hyosetsu/ec2-kadai/blob/main/Dockerfile から
- nginx/conf.d/default.confはhttps://github.com/hyosetsu/ec2-kadai/blob/main/nginx/conf.d/default.conf から

### ファイルを作る
```
vim public/bbs.php
```
等でファイルを編集する
https://github.com/hyosetsu/ec2-kadai/blob/main/public/bbs.php
を書く

```
vim public/style.css
```
でcssファイルを編集する
https://github.com/hyosetsu/ec2-kadai/blob/main/public/style.css
を書く

### データベースを作る
```
docker compose exec mysql mysql example_db
```
でmysqlに接続

example_dbがすでに選ばれているのでlog.sqlに書いてある
```
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` TEXT NOT NULL,
  `email` TEXT NOT NULL,
  `password` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE `users` ADD COLUMN icon_filename TEXT DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN introduction TEXT DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN cover_filename TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN birthdate DATE DEFAULT NULL;

CREATE TABLE `bbs_entries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `image_filename` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `user_relationships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `followee_user_id` INT UNSIGNED NOT NULL,
  `follower_user_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
でテーブルを作る

これで作成完了

