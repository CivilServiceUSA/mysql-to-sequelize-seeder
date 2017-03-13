![Civil Services Logo](https://cdn.civil.services/common/github-logo.png "Civil Services Logo")

Civil Services API
===

[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](https://raw.githubusercontent.com/CivilServiceUSA/geojson-to-mysql/master/LICENSE) [![GitHub contributors](https://img.shields.io/github/contributors/CivilServiceUSA/geojson-to-mysql.svg)](https://github.com/CivilServiceUSA/geojson-to-mysql/graphs/contributors)

__Civil Services__ is a collection of tools that make it possible for citizens to be a part of what is happening in their Local, State & Federal Governments.


MySQL to Sequelize Seeder
---

__IMPORTANT__:  This is an internal tool with a very specific function for Civil Services that exports GeoJSON data from MySQL tables to create Sequelize Seeder Files.

Use PHP to Connect to MySQL and Generate Sequelize Seeder File.

Copy `config.ini.dist` to `config.ini` and add your own values to it.

#### Usage:

```bash
php /path/to/export.php --table zipcodes --order zipcode --sort ASC --where "zipcode like '99%'" --output "./app/seeders/20170301000000-shape-zipcode-99-seeder.js"
```

