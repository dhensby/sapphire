---
title: Relations between Records
summary: Relate models together using the ORM using has_one, has_many, and many_many.
icon: link
---
# Relations between Records

In most situations you will likely see more than one [api:DataObject] and several classes in your data model may relate
to one another. An example of this is a `Player` object may have a relationship to one or more `Team` or `Coach` classes
and could take part in many `Games`. Relations are a key part of designing and building a good data model.

Relations are built through static array definitions on a class, in the format `<relationship-name> => <classname>`.
SilverStripe supports a number of relationship types and each relationship type can have any number of relations.

## has_one

A 1-to-1 relation creates a database-column called "`<relationship-name>`ID", in the example below this would be 
"TeamID" on the "Player"-table.

```php
	<?php

	class Team extends DataObject {

		private static $db = array(
			'Title' => 'Varchar'
		);

		private static $has_many = array(
			'Players' => 'Player'
		);
	}

	class Player extends DataObject {

	  private static $has_one = array(
	    "Team" => "Team",
	  );
	}

```
and provides a short syntax for accessing the related object.

At the database level, the `has_one` creates a `TeamID` field on `Player`. A `has_many` field does not impose any database changes. It merely injects a new method into the class to access the related records (in this case, `Players()`)

```php
	$player = Player::get()->byId(1);

	$team = $player->Team();
	// returns a 'Team' instance.

	echo $player->Team()->Title;
	// returns the 'Title' column on the 'Team' or `getTitle` if it exists.

```
	
```ss
	<% with $Player %>
		<% if $Team %>
			Plays for $Team.Title
		<% end_if %>
	<% end_with %>

```

A has_one can also be polymorphic, which allows any type of object to be associated.
This is useful where there could be many use cases for a particular data structure.

An additional column is created called "`<relationship-name>`Class", which along
with the ID column identifies the object.

To specify that a has_one relation is polymorphic set the type to 'DataObject'.
Ideally, the associated has_many (or belongs_to) should be specified with dot notation.

```
	::php

	class Player extends DataObject {
		private static $has_many = array(
			"Fans" => "Fan.FanOf"
		);
	}

	class Team extends DataObject {
		private static $has_many = array(
			"Fans" => "Fan.FanOf"
		);
	}

	// Type of object returned by $fan->FanOf() will vary
	class Fan extends DataObject {

		// Generates columns FanOfID and FanOfClass
		private static $has_one = array(
			"FanOf" => "DataObject"
		);
	}

```
Note: The use of polymorphic relationships can affect query performance, especially
on joins, and also increases the complexity of the database and necessary user code.
They should be used sparingly, and only where additional complexity would otherwise
be necessary. E.g. Additional parent classes for each respective relationship, or
duplication of code.
[/warning]

## has_many

Defines 1-to-many joins. As you can see from the previous example, `$has_many` goes hand in hand with `$has_one`.

[alert]
Please specify a $has_one-relationship on the related child-class as well, in order to have the necessary accessors
available on both ends.
[/alert]

```php
	<?php

	class Team extends DataObject {

		private static $db = array(
			'Title' => 'Varchar'
		);

		private static $has_many = array(
			'Players' => 'Player'
		);
	}

	class Player extends DataObject {

	  private static $has_one = array(
	    "Team" => "Team",
	  );
	}

```
you will get an instance of [api:HasManyList] rather than the object.

```php
	$team = Team::get()->first();

	echo $team->Players();
	// [HasManyList]

	echo $team->Players()->Count();
	// returns '14';

	foreach($team->Players() as $player) {
		echo $player->FirstName;
	}

```

```php
	<?php

	class Person extends DataObject {

		private static $has_many = array(
			"Managing" => "Company.Manager",
			"Cleaning" => "Company.Cleaner",
		);
	}
	
	class Company extends DataObject {

		private static $has_one = array(
			"Manager" => "Person",
			"Cleaner" => "Person"
		);
	}

```
Multiple `$has_one` relationships are okay if they aren't linking to the same object type. Otherwise, they have to be
named.

If you're using the default scaffolded form fields with multiple `has_one` relationships, you will end up with a CMS field for each relation. If you don't want these you can remove them by their IDs:

```php
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(array('ManagerID', 'CleanerID'));
        return $fields;
    }

```
## belongs_to

Defines a 1-to-1 relationship with another object, which declares the other end of the relationship with a 
corresponding `$has_one`. A single database column named `<relationship-name>ID` will be created in the object with the 
`$has_one`, but the $belongs_to by itself will not create a database field. This field will hold the ID of the object 
declaring the `$belongs_to`.

Similarly with `$has_many`, dot notation can be used to explicitly specify the `$has_one` which refers to this relation. 
This is not mandatory unless the relationship would be otherwise ambiguous.

```php
	<?php

	class Team extends DataObject {
		
		private static $has_one = array(
			'Coach' => 'Coach'
		);
	}

	class Coach extends DataObject {
		
		private static $belongs_to = array(
			'Team' => 'Team.Coach'
		);
	}

```
## many_many

Defines many-to-many joins. A new table, (this-class)_(relationship-name), will be created with a pair of ID fields.

[warning]
Please specify a $belongs_many_many-relationship on the related class as well, in order to have the necessary accessors 
available on both ends.
[/warning]

```php
	<?php

	class Team extends DataObject {

	  private static $many_many = array(
	    "Supporters" => "Supporter",
	  );
	}

	class Supporter extends DataObject {

	  private static $belongs_many_many = array(
	    "Supports" => "Team",
	  );
	}

```
you will get an instance of [api:ManyManyList] rather than the object.

```php
	$team = Team::get()->byId(1);

	$supporters = $team->Supporters();
	// returns a 'ManyManyList' instance.

```
The relationship can also be navigated in [templates](../templates).
	
```ss
	<% with $Supporter %>
		<% loop $Supports %>
			Supports $Title
		<% end_if %>
	<% end_with %>

```

```php
	<?php

	class Category extends DataObject {
		
		private static $many_many = array(
			'Products' => 'Product',
			'FeaturedProducts' => 'Product'
		);
	}

	class Product extends DataObject {
		
		private static $belongs_many_many = array(
			'Categories' => 'Category.Products',
			'FeaturedInCategories' => 'Category.FeaturedProducts'
		);
	}

```

If you're unsure about whether an object should take on `many_many` or `belongs_many_many`, the best way to think about it is that the object where the relationship will be edited (i.e. via checkboxes) should contain the `many_many`. For instance, in a `many_many` of Product => Categories, the `Product` should contain the `many_many`, because it is much more likely that the user will select Categories for a Product than vice-versa.


## Adding relations

Adding new items to a relations works the same, regardless if you're editing a **has_many** or a **many_many**. They are 
encapsulated by [api:HasManyList] and [api:ManyManyList], both of which provide very similar APIs, e.g. an `add()`
and `remove()` method.

```php
	$team = Team::get()->byId(1);

	// create a new supporter
	$supporter = new Supporter();
	$supporter->Name = "Foo";
	$supporter->write();

	// add the supporter.
	$team->Supporters()->add($supporter);

```
## Custom Relations

You can use the ORM to get a filtered result list without writing any SQL. For example, this snippet gets you the 
"Players"-relation on a team, but only containing active players.

See [api:DataObject::$has_many] for more info on the described relations.

```php
	<?php

	class Team extends DataObject {

	  private static $has_many = array(
	    "Players" => "Player"
	  );
	
	  public function ActivePlayers() {
	  	return $this->Players()->filter('Status', 'Active');
	  }
	}

```
Adding new records to a filtered `RelationList` like in the example above doesn't automatically set the filtered 
criteria on the added record.
[/notice]

## Relations on Unsaved Objects

You can also set *has_many* and *many_many* relations before the `DataObject` is saved. This behavior uses the 
[api:UnsavedRelationList] and converts it into the correct `RelationList` when saving the `DataObject` for the first 
time.

This unsaved lists will also recursively save any unsaved objects that they contain.

As these lists are not backed by the database, most of the filtering methods on `DataList` cannot be used on a list of 
this type. As such, an `UnsavedRelationList` should only be used for setting a relation before saving an object, not 
for displaying the objects contained in the relation.

## Related Documentation

* [Introduction to the Data Model and ORM](data_model_and_orm)
* [Lists](lists)

## API Documentation

* [api:HasManyList]
* [api:ManyManyList]
* [api:DataObject]
