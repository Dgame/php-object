# php-object

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Dgame/php-object/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Dgame/php-object/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Dgame/php-object/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Dgame/php-object/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/Dgame/php-object/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Dgame/php-object/build-status/master)
[![StyleCI](https://styleci.io/repos/87475805/shield?branch=master)](https://styleci.io/repos/87475805)
[![Build Status](https://travis-ci.org/Dgame/php-object.svg?branch=master)](https://travis-ci.org/Dgame/php-object)

----
An intelligent facade around your object to protect it from any harm.
----

### hasMethod
```php
$facade = new ObjectFacade(new Exception());
$this->assertTrue($facade->hasMethod('getMessage'));
$this->assertFalse($facade->hasMethod('foo'));
```
### hasProperty
```php
$facade = new ObjectFacade(new Exception());
foreach (['message', 'code', 'file', 'line'] as $property) {
    $this->assertTrue($facade->hasProperty($property));
}
$this->assertFalse($facade->hasProperty('foo'));
```

### getPropertyByName
```php
$facade = new ObjectFacade(new Exception());
foreach (['message', 'code', 'file', 'line'] as $name) {
    $property = $facade->getPropertyByName($name);

    $this->assertNotNull($property);
    $this->assertEquals($name, $property->getName());
    $this->assertNotEquals(0, ReflectionProperty::IS_PROTECTED & $property->getModifiers());
}
```

### getMethodByName
```php
$facade = new ObjectFacade(new Exception());
$method = $facade->getMethodByName('getMessage');

$this->assertNotNull($method);
$this->assertEquals('getMessage', $method->getName());
$this->assertNotEquals(0, (ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_FINAL) & $method->getModifiers());
```

### getGetterMethod
```php
$facade = new ObjectFacade(new Exception());
$method = $facade->getGetterMethod('message');

$this->assertNotNull($method);
$this->assertEquals('getMessage', $method->getName());
```

### getSetterMethod
```php
$facade = new ObjectFacade(
    new class() {
        public function setFoo()
        {
        }
    }
);

$method = $facade->getSetterMethod('foo');

$this->assertNotNull($method);
$this->assertEquals('setFoo', $method->getName());
```

### getValueByMethod
```php
$exception = new Exception('Test');
$facade    = new ObjectFacade($exception);

$this->assertEquals($exception, $facade->getObject());
$this->assertEquals('Test', $facade->getValueByMethod('message'));
$this->assertEquals($exception->getMessage(), $facade->getValueByMethod('message'));
$this->assertEquals($exception->getFile(), $facade->getValueByMethod('file'));
$this->assertEquals($exception->getLine(), $facade->getValueByMethod('line'));
$this->assertNull($facade->getValueByMethod('unknown'));
```

### getValueByProperty
```php
$facade = new ObjectFacade(
    new class() {
        public $foo = 42;
        public $bar = Exception::class;
    }
);

$this->assertEquals(42, $facade->getValueByProperty('foo'));
$this->assertEquals(Exception::class, $facade->getValueByProperty('bar'));
$this->assertNull($facade->getValueByProperty('unknown'));

$facade = new ObjectFacade(new Exception());

$this->assertNull($facade->getValueByProperty('line')); // not a public property
$this->assertNull($facade->getValueByProperty('file')); // not a public property
```

### setValueByMethod
```php
$facade = new ObjectFacade(
  new class() {
      private $foo = 42;
      private $bar;

      public function setFoo(int $foo)
      {
          $this->foo = $foo;
      }

      public function setFooBar()
      {
          $this->foo = 1;
          $this->bar = 2;
      }

      public function getFoo(): int
      {
          return $this->foo;
      }

      public function setBar(int $bar = null)
      {
          $this->bar = $bar;
      }

      public function getBar()
      {
          return $this->bar;
      }
  }
);

$this->assertEquals(42, $facade->getValueByMethod('foo'));
$facade->setValueByMethod('foo', 23);
$this->assertEquals(23, $facade->getValueByMethod('foo'));
$facade->setValueByMethod('foo', null); // "setFoo" does not accept null => keep the old value
$this->assertEquals(23, $facade->getValueByMethod('foo'));
$facade->setValueByMethod('foo', 'abc'); // "setFoo" does not accept a string => keep the old value
$this->assertEquals(23, $facade->getValueByMethod('foo'));

$this->assertNull($facade->getValueByMethod('bar'));
$facade->setValueByMethod('bar', 1337);
$this->assertEquals(1337, $facade->getValueByMethod('bar'));
$facade->setValueByMethod('bar', null);
$this->assertNull($facade->getValueByMethod('bar'));

$facade->setValueByMethod('foobar', uniqid());
$this->assertEquals(1, $facade->getValueByMethod('foo'));
$this->assertEquals(2, $facade->getValueByMethod('bar'));
```

### setValueByProperty
```php
$facade = new ObjectFacade(
    new class() {
        public $foo = 42;
    }
);

$this->assertEquals(42, $facade->getValueByProperty('foo'));
$this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
$facade->setValueByProperty('foo', 23);
$this->assertEquals(23, $facade->getValueByProperty('foo'));
$this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
$facade->setValueByProperty('foo', null);
$this->assertNull($facade->getValueByProperty('foo'));
$this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
$facade->setValueByProperty('foo', 1337);
$this->assertEquals(1337, $facade->getValueByProperty('foo'));
$this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
```

### setValue / getValue
```php
$facade = new ObjectFacade(
    new class() {
        public $foo = 42;
    }
);

$facade->setValue('foo', 3537);
$this->assertEquals(3537, $facade->getValue('foo'));
```

```php
new class() {
    private $foo = 42;

    public function setFoo(int $foo)
    {
        $this->foo = $foo;
    }
}

$facade->setValue('foo', 3537);
$this->assertEquals(3537, $facade->getValue('foo'));
```
