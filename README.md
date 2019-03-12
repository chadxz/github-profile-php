# github-profile-php

Playground for messing with php and consuming a graphql client.

Inspired by 
[https://github.com/wemake-services/meta/issues/7](https://github.com/wemake-services/meta/issues/7)

### development

```
$ make dev
$ open http://localhost:8000
```

This will run the application, mounting your local source into the Docker
container and enabling xdebug. Configure your IDE to listen for xdebug
connections to receive breakpoints.

### deployment

This repository will automatically deploy to heroku at 
[https://github-profile-php.herokuapp.com/](https://github-profile-php.herokuapp.com/)
when commits are pushed to master.

To do a manual deploy, install the heroku cli, add the remote, and push:

```
$ brew install heroku/brew/heroku
$ git remote add heroku git@heroku.com:github-profile-php.git
$ git push heroku master
```

### heroku

The heroku app must have `runtime-new-layer-extract` lab enabled to work around
a bug with the way heroku treats apache docker containers. Enable this with:

```
heroku labs:enable --app=github-profile-php runtime-new-layer-extract
```
