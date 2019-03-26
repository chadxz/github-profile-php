# github-profile-php

[![Build Status][Build Status Image]][Build Status Link]
[![Codecov][Codecov Image]][Codecov Link]

[Build Status Image]: https://travis-ci.org/chadxz/github-profile-php.svg?branch=master
[Build Status Link]: https://travis-ci.org/chadxz/github-profile-php
[Codecov Image]: https://img.shields.io/codecov/c/github/chadxz/github-profile-php.svg
[Codecov Link]: https://codecov.io/gh/chadxz/github-profile-php

Playground for messing with php.

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

The following environment variables should be configured:

- **GITHUB_CLIENT_ID**: The client id of the Github App to use for this app
- **GITHUB_CLIENT_SECRET**: The client secret of the Github App to use for this app

The Github App should be configured with the "User authorization callback" set
to the base url of your app, with the path of `/github/auth-callback`
 
### heroku

The heroku app must have `runtime-new-layer-extract` lab enabled to work around
a bug with the way heroku treats apache docker containers. Enable this with:

```
heroku labs:enable --app=github-profile-php runtime-new-layer-extract
```
