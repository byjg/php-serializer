# ObjectCopy Interface 

ObjectCopyInterface is an interface that exposes the methods `copyFrom` and `copyTo` that allows set property contents from/to another object.

You can either implement this interface or extend the class `ObjectCopy`

```php
<?php
// Create the class
class MyClass extends ObjectCopy
{}

// Copy the properties from $data into the properties that match on $myclass
$myclass->copyFrom($data);

// Copy the properties from $myclass into the properties that match on $otherObject
$myclass->copyTo($otherobject);
```
