# php-mysql
php mysql eloquent


# Usage

select with search & order & pagination
```php
$this->db->sql("SELECT a.id, a.name, a.mobile, a.date, b.id as admin_id, b.name as admin_name FROM admin as a LEFT JOIN admin as b ON a.adminid = b.id")
		

		->whereAccess( "a.adminid", $this->adminids, $this->authorize )

		->search(
			  [ "a.name", "a.mobile", "a.email" ]
			, @$this->get['search'] )
		
		->searchByKey( [ 
			[ "id" =>  [ "a.id", "=" ] ],
			[ "name" => [ "a.name", "like" ] ],
		], $this->get )
		
		->orderKey([ 
			"id" => "a.id", 
			"name" => "a.name",
			"date" => "a.date",
			], $this->get )

		
		->order("a.id", "DESC")

		
		->pagination( $this->get )

		
		->find();
```

Update query example
```php
$this->db
			->table("admin")
			->where("id", "=", $id )
			->whereAccess("adminid", $this->adminids, $this->authorize )
			->update([
				'name' => $this->post["name"],
				'count_order' => $this->post["count_order"],
				'attachment' => implode( ",", $this->post["upload"]),
				'mobile' => $this->post["mobile"],
				'email' => $this->post["email"],
				'city' => $this->post["city"],
				'username' => $this->post["username"],
				'about' => $this->post["about"],
				'chatid_bot' => $this->post["chatid_bot"],
				'state' => $this->post["state"],
				'access' => @implode(",", $this->post["access"]),
				])
```


Insert query 

```php

$this->db->table("admin")->insert( 
			[
			"name" => $this->post["name"],
			"count_order" => $this->post["count_order"],
			"username" => $this->post["username"],
			"password" => $this->encrypt( $this->post["password"] ),
			"mobile" => $this->post["mobile"],
			"email" => $this->post["email"],
			"state" => $this->post["state"],
			"city" => $this->post["city"],
			"attachment" = @(string)implode( ",", $this->post["upload"] ),
			"access" = (string)@implode(",", $this->post["access"]),
			"about" => $this->post["about"],
			"chatid_bot" => $this->post["chatid_bot"],
			"adminid" = (int)@$this->authorize["id"],
			"date" = time(),
			]
);

```
