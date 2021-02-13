# EaseAmpMysqlHalite
> A very simple and safe PHP library to execute SQL Queries as Prepared Statements on MySQL Database, in an asynchronous & non-blocking all basing upon amphp/mysql package. Additional checks are supported in terms of facilitating creation and verification of row level digital signature for different database tables along with creation of  blind indexes of data in encrypted db columns, using easeappphp/ea-halite package.

### Why EaseAmpMysqlHalite?
This helps writing asynchronous & non-blocking sql queries while using application scoped encryption & digital signature options, using readily available methods in this library.

### Advantages
- Uses prepared statements
- MySQL/MariaDB Connection object supported at present
- Named parameters syntax, similar to that of PDO syntax, is supported
- Can encrypt and digital sign content using authenticated encryption strategies and then store in the database. Database row level checks can be made to verify the authenticity of data and blind indexes can be used to support with full content searches.

## License
This software is distributed under the [MIT](https://opensource.org/licenses/MIT) license. Please read [LICENSE](https://github.com/easeappphp/PDOLight/blob/main/LICENSE) for information on the software availability and distribution.
