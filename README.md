# Lara Tools - Convenient tools for Laravel

## Contents

1. <a href="#LaraUtil">LaraUtil</a>
	* <a href="#hasTable">hasTable</a>
  	* <a href="#hasColumn">hasColumn</a>
  	* <a href="#getFullColumns">getFullColumns</a>
  	* <a href="#hashPassword">hashPassword</a>
  	* <a href="#verifyPassword">verifyPassword</a>
2. <a href="#ModelExtrasTrait">ModelExtrasTrait</a>
	* <a href="#saveAssocited">saveAssociated</a>


## <a id="LaraUtil"></a>LaraUtil

LaraUtil contains the following utility methods

### <a id="hasTable"></a>hasTable

Checks if the given table exists - caching the result, returns true or false

```
LaraUtil::hasTable('users')
```

### <a id="hasColumn"></a>hasColumn

Checks if the given table has the given column - caching the query, returns true or false

```
LaraUtil::hasColumn('users', 'first_name')
```

	
### <a id="getFullColumns"></a>getFullColumns

Accepts the columns list and the table name and adds the table name into columns if does not exist e.g.

```
$columns = ['id', 'first_name', 'users.last_name'];
$columns = LaraUtil::getFullColumns($columns, 'users');

// the final array will look like
['users.id', 'users.first_name', 'users.last_name']
```

### <a id="hashPassword"></a>hashPassword

Hashes the given string by bcrypt, however afterwards encrypting the password's hash by application-side key. It also applies `sha256` method (before hashing) to remove bcrypt's length restriction - [more](https://security.stackexchange.com/a/6627/38200)

```
$hashedAndEcryptedPassword = LaraUtil::hashPassword('some password');
```
will be string like this
`eyJpdiI6IlU4amxZaVNCc2xjemlkZUNWRFVhb3c9PSIsInZhbHVlIjoidWs0bmRcL1JFMHk1dUE4Yk9kWFo3b2VSZEJuYXk5NngwUXMxMDBieTdvOVZ6d1JWQ3RObVE3RGZmcHlqYnV1Ymw5OFVKelRlb2JsSllcL21FVlk4WklVNHkzcnl5Ym90T0tJVzNZalRyUmI2dz0iLCJtYWMiOiI2MDE3ZTQ1NGE0NDcwNTY2Yjc3NzAyZmZlOWU4ZDBkMTE4ODNhNTY0YTE2ZmYzNDNkNDA0ZGI2ZWRhZjhjMTA3In0=`

### <a id="verifyPassword"></a>verifyPassword

Verifies the password hashed by `hashPassword` method above - returns true or false

```
$passwordMatch = verifyPassword('plan text password', $hashedAndEcryptedPassword);
```



## <a id="ModelExtrasTrait"></a>ModelExtrasTrait

ModelExtrasTrait is a trait to be used in Models - provides the following methods


### <a id="saveAssociated"></a>saveAssociated

`saveAssociated` method is a wrapper method, that allows to save `BelongsToMany` and `HasMany` related models in a single transaction, e.g. suppose we need to save a product with its related categories, we would use

```
Product::saveAssociated($data, ['associated' => 'categories']);
```

the `$data` should be an array like this

```
$data = ['name', 'price', 'categories_ids' => [1, 3, 7]]
```





