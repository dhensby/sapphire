---
title: Extend the CMS interface
summary: Customise the UI of the CMS backend
---

# How to extend the CMS interface

## Introduction

The CMS interface works just like any other part of your website: It consists of
PHP controllers, templates, CSS stylesheets and JavaScript. Because it uses the
same base elements, it is relatively easy to extend.

As an example, we're going to add a permanent "bookmarks" link list to popular pages
into the main CMS menu. A page can be bookmarked by a CMS author through a
simple checkbox.

For a deeper introduction to the inner workings of the CMS, please refer to our
guide on [CMS Architecture](/developer_guides/customising_the_admin_interface/cms_architecture).

## Overload a CMS template

If you place a template with an identical name into your application template
directory (usually `mysite/templates/`), it'll take priority over the built-in
one.

CMS templates are inherited based on their controllers, similar to subclasses of
the common `Page` object (a new PHP class `MyPage` will look for a `MyPage.ss` template).
We can use this to create a different base template with `LeftAndMain.ss`
(which corresponds to the `LeftAndMain` PHP controller class).

Copy the template markup of the base implementation at `framework/admin/templates/Includes/LeftAndMain_Menu.ss`
into `mysite/templates/Includes/LeftAndMain_Menu.ss`. It will automatically be picked up by
the CMS logic. Add a new section into the `<ul class="cms-menu-list">`

```ss
	...
	<ul class="cms-menu-list">
		<!-- ... -->
		<li class="bookmarked-link first">
			<a href="admin/pages/edit/show/1">Edit "My popular page"</a>
		</li>
		<li class="bookmarked-link last">
			<a href="admin/pages/edit/show/99">Edit "My other page"</a>
		</li>
	</ul>
	...

```
hardcoded links underneath the left-hand menu. We'll make these dynamic further down.

## Include custom CSS in the CMS

In order to show the links a bit separated from the other menu entries,
we'll add some CSS, and get it to load
with the CMS interface. Paste the following content into a new file called
`mysite/css/BookmarkedPages.css`:

```css
	.bookmarked-link.first {margin-top: 1em;}

```
[configuration value](../../configuration).

```yml
	LeftAndMain:
	  extra_requirements_css:
	    - mysite/css/BookmarkedPages.css

```

Now we'll define which pages are actually bookmarked, a flag that is stored in
the database. For this we need to decorate the page record with a
`DataExtension`. Create a new file called `mysite/code/BookmarkedPageExtension.php`
and insert the following code.

```php
	<?php

	class BookmarkedPageExtension extends DataExtension {

		private static $db = array(
			'IsBookmarked' => 'Boolean'
		);

		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab('Root.Main',
				new CheckboxField('IsBookmarked', "Show in CMS bookmarks?")
			);
		}
	}

```

```yml
	SiteTree:
	  extensions:
	    - BookmarkedPageExtension

```
Refresh the CMS, open a page for editing and you should see the new checkbox.

## Retrieve the list of bookmarks from the database

One piece in the puzzle is still missing: How do we get the list of bookmarked
pages from the database into the template we've already created (with hardcoded
links)? Again, we extend a core class: The main CMS controller called
`LeftAndMain`.

Add the following code to a new file `mysite/code/BookmarkedLeftAndMainExtension.php`;

```php
	<?php

	class BookmarkedPagesLeftAndMainExtension extends LeftAndMainExtension {

		public function BookmarkedPages() {
			return Page::get()->filter("IsBookmarked", 1);
		}
	}

```

```yml
	LeftAndMain:
	  extensions:
	    - BookmarkedPagesLeftAndMainExtension

```
Find the `<ul>` you created earlier in `mysite/admin/templates/LeftAndMain.ss`
and replace it with the following:

```ss
	<ul class="cms-menu-list">
		<!-- ... -->
		<% loop $BookmarkedPages %>
		<li class="bookmarked-link $FirstLast">
			<li><a href="admin/pages/edit/show/$ID">Edit "$Title"</a></li>
		</li>
		<% end_loop %>
	</ul>

```

CMS actions follow a principle similar to the CMS fields: they are built in the
backend with the help of `FormFields` and `FormActions`, and the frontend is
responsible for applying a consistent styling.

The following conventions apply:

* New actions can be added by redefining `getCMSActions`, or adding an extension
with `updateCMSActions`.
* It is required the actions are contained in a `FieldSet` (`getCMSActions`
returns this already).
* Standalone buttons are created by adding a top-level `FormAction` (no such
button is added by default).
* Button groups are created by adding a top-level `CompositeField` with
`FormActions` in it.
* A `MajorActions` button group is already provided as a default.
* Drop ups with additional actions that appear as links are created via a
`TabSet` and `Tabs` with `FormActions` inside.
* A `ActionMenus.MoreOptions` tab is already provided as a default and contains
some minor actions.
* You can override the actions completely by providing your own
`getAllCMSFields`.

Let's walk through a couple of examples of adding new CMS actions in `getCMSActions`.

First of all we can add a regular standalone button anywhere in the set. Here
we are inserting it in the front of all other actions. We could also add a
button group (`CompositeField`) in a similar fashion.

```php
	$fields->unshift(FormAction::create('normal', 'Normal button'));

```
already present in the `FieldList`.

```php
	$fields->fieldByName('MajorActions')->push(FormAction::create('grouped', 'New group button'));

```
infrequently used minor actions.

```php
	$fields->addFieldToTab('ActionMenus.MoreOptions', FormAction::create('minor', 'Minor action'));

```
`TabSet`.

```php
	$fields->addFieldToTab('ActionMenus.MyDropUp', FormAction::create('minor', 'Minor action in a new drop-up'));

```
Empty tabs will be automatically removed from the `FieldList` to prevent clutter.
[/hint]

To make the actions more user-friendly you can also use alternating buttons as
detailed in the [CMS Alternating Button](cms_alternating_button)
how-to.

### Implementing handlers

Your newly created buttons need handlers to bind to before they will do anything.
To implement these handlers, you will need to create a `LeftAndMainExtension` and add
applicable controller actions to it:

```php
	class CustomActionsExtension extends LeftAndMainExtension {
		
		private static $allowed_actions = array(
        	'sampleAction'
    	);
    	
    	public function sampleAction()
    	{
    		// Create the web
    	}
    	
    }
    
```
The extension then needs to be registered:

```yaml
	LeftAndMain:
		extensions:
			- CustomActionsExtension
			

```

```php
	$fields->push(FormAction::create('sampleAction', 'Perform Sample Action'));

```

In a few lines of code, we've customised the look and feel of the CMS.

While this example is only scratching the surface, it includes most building
blocks and concepts for more complex extensions as well.

## Related

 * [Reference: CMS Architecture](../cms_architecture)
 * [Reference: Layout](../cms_layout)
 * [Rich Text Editing](/developer_guides/forms/field_types/htmleditorfield)
 * [CMS Alternating Button](cms_alternating_button)
