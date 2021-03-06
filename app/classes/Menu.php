<?php
/**
 	Filename:   Menu.php
 	Date:       2016-01-20
 	Author:     Lars Veldscholte
 	            lars@veldscholte.eu
 	            http://lars.veldscholte.eu

 	Copyright 2016 Lars Veldscholte

 	This file is part of RadiusAdmin.

 	RadiusAdmin is free software: you can redistribute it and/or modify
 	it under the terms of the GNU General Public License as published by
 	the Free Software Foundation, either version 3 of the License, or
 	(at your option) any later version.

 	RadiusAdmin is distributed in the hope that it will be useful,
 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 	GNU General Public License for more details.

 	You should have received a copy of the GNU General Public License
 	along with RadiusAdmin. If not, see <http://www.gnu.org/licenses/>.
 */

class Menu {
	private $db;
	private $parent_id;
	private $levels;
	private $baseid;
	private $menuitems = [];

	function __construct(PDO $db, int $parent_id, int $levels = 1, int $baseid = null) {
		$this->db = $db;
		$this->parent_id = $parent_id;
		$this->levels = $levels;
		$this->baseid = $baseid;
		$this->populate();
	}

	private function populate() {
		$stmt = $this->db->prepare("SELECT id, parent_id, page, options, title, glyphicon, activeonly FROM menu WHERE parent_id = :parent_id ORDER BY id ASC");
		$stmt->bindParam(":parent_id", $this->parent_id, PDO::PARAM_INT);
		$stmt->execute();

		// Call addMenuItem() for every row
		$stmt->fetchAll(PDO::FETCH_FUNC, [$this, "addMenuItem"]);
	}

	function addMenuItem(int $id, int $parent_id, string $mpage, string $options, string $title, string $glyphicon, bool $activeonly) {
		global $page;
		$item = new Menuitem($id, $parent_id, $mpage, $options, $title, $glyphicon, $activeonly);

		// If current page is this item, make it active
		// If this is the topmenu ($parent_id=0), also make menuitem active if current page is child
		$item->setActive(($page == $mpage) || ($this->baseid == $id));

		// If levels > 1, menuitem should recursively lookup for submenuitems
		if($this->levels > 1) {
			$item->populateSubmenu($this->db, $this->levels - 1);
		}

		$this->menuitems[] = $item;
	}

	function getMenuData(): array {
		// Return an array of Menuitems
		return $this->menuitems;
	}
}