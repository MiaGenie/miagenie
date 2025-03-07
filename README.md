## MiaGenie Development Environment, clone repo and run:

### Copy environment and docker composer setup files

cp .env.example .env \
cp docker-compose.dev.yml docker-compose.yml

### Clone git repos to local folder
git clone https://github.com/MiaGenie/mixpost-pro-team -b dev packages/inovector/mixpost-pro-team \
git clone https://github.com/MiaGenie/mixpost-enterprise -b dev packages/inovector/mixpost-enterprise

### Copy editor config files
cp editor.config.js packages/inovector/mixpost-pro-team/editor.config.js \
cp editor.config.js packages/inovector/mixpost-enterprise/editor.config.js

### Install dependencies
./composer-dev install

### Start docker containers
sail up -d

### Instal npm packages
sail npm install

### Publish mixpost migrations
sail artisan mixpost:publish \
sail artisan mixpost:publish --enterprise \
sail artisan migrate

### Publish laravel public storage
sail artisan storage:link

