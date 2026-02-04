# 実装手順
- Docker系のインストール
```sh
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
```

dockerをインストールしdockerグループに追加

usermodを反映するために一度ログアウトする

- Docker Composeのインストール
```sh
sudo mkdir -p /usr/local/lib/docker/cli-plugins/
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```
インストールできたかの確認
```sh
docker compose version
```

## Docker系のインストールができたので構築していく
### gitからクローンする
```sh
git clone git@github.com:hyosetsu/ec2-kadai.git
```
か
```sh
git clone https://github.com/hyosetsu/ec2-kadai.git
```
でクローンする
### コンテナイメージを作成する
クローンし終わったら、
```sh
cd ec2-kadai
```
でクローンしたディレクトリ内に行き、以下を実行する
```sh
docker compose build
```
これが終わったら、イメージ作成完了

### データベースを作る
イメージを作り終わったら、
```sh
docker compose exec mysql mysql kadai_db
```
でmysqlに接続

kadai_dbがすでに選ばれているのでlog.sqlに書いてある
```sql
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
でテーブルを作り、データベースの確認で
```sql
show tables;
```
で上記で作ったテーブルが出たら、データベース作成完了

## 作り終わったので起動する
```sh
docker compose up
```
して起動して、ページにアクセスする
