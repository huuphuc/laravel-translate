# laravel-translate

A simple way to make multilanguage system using eloquent.
Restricting your source code changes.

**- A simple exsample: we already have that table**
```mysql
CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(256) NOT NULL,
  `image` varchar(256) DEFAULT NULL,
  `content` text NOT NULL,
  `rate` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
```

So now we will make another table to contain "translate" of this table.


**- New table we need to make**
```mysql
CREATE TABLE `pages_trans` (
  `id` int(10) UNSIGNED NOT NULL,
  `_locale` varchar(8) NOT NULL,
  `title` varchar(256) NOT NULL,
  `image` varchar(256) DEFAULT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `pages_trans`
  ADD PRIMARY KEY (`id`,`_locale`);
```

that table look like same with our original table. Every field you want "translate" it, put it to "pages_trans" table; and we use "_locale" field to store language code for "translation". Just remember that primary key of "pages_trans" is primary key of "pages".


**- Next we update our model**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Huuphuc\Translate\Translatable;

class Page extends Model {

    use Translatable;

    protected $table = 'pages';

}
```
add Translatable train to your model


**- Now you can use translate function any where you need**
```php
$pageModel->trans('jp');
echo $pageModel->title; // echo japanese
$pageModel->trans('xyz');
echo $pageModel->title; // echo xyz language ...
```
of course if you don't have "translation" yet, default data will be use.


**- And update your "translation"**
```php
$pageModel->trans('vi');
$pageModel->title = "Xin chào";
$pageModel->content = "Đây là bản Tiếng Việt";
$pageModel->save();

$pageModel->trans('jp');
$pageModel->update([
	'title' => 'こんにちは', 
	'content' => 'これは日本語版です'
]);
```


**- How to install it?**
```bash
composer require huuphuc/translate:dev-master
```

**- Manual install**
Just copy Translatable.php to any where in your project.

Have fun. It not the best solution but simple and I don't need to change my code much.


