# An extension to laravel for quick develpment

Query builder needs to overwrite with karla

```php
sed -i -e 's/use Illuminate\\Database\\Query\\Builder/use Karla\\Database\\Query\\Builder/g' vendor/laravel/framework/src/Illuminate/Database/Connection.php

sed -i -e 's/use Illuminate\\Database\\Grammar/use Karla\\Database\\Grammar/g' vendor/laravel/framework/src/Illuminate/Database/Query/Grammars/Grammar.php

```

```php
    php artisan vendor:publish --provider="Karla\KarlaServiceProvider" --tag="config"
    php artisan vendor:publish --provider="Karla\KarlaServiceProvider" --tag="assets"
    php artisan vendor:publish --provider="Karla\KarlaServiceProvider" --tag="views"
```

add in kernal.php route middleware
```php

'auth.verified' => \Karla\Http\Controllers\Auth\Middleware\IsUserActivate::class,
```

## Config changes

add in auth.php guards array
```php
    'token' => [
        'driver' => 'access_token',
        'provider' => 'token'
    ],
```

Replace passwords array with below
```php
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'auth_password_resets',
            'expire' => 60,
        ],
    ],
```

Add in providers array in app.php
```php
    //app.php
    
    Karla\View\ViewServiceProvider::class,
```

###Sorting task

```html
<tbody ajax-content class="table_sortable_body">
    ...
    <td sortable>
        <i class="fa fa-arrows-v fa-lg"></i>
        <input type="hidden" name="sorting[{{ $row->id }}]" value="{{ $row->ordering }}">
    </td>
```

```php
    if ($task == 'sorting') {
        $sorting = $this->input('sorting');
        $this->get('resolver')->getHelper('speed')->sorting('table', $sorting, 'id');

        return [];
    }
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.