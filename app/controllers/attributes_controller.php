<?php
class AttributesController extends AppController {

	var $name = 'Attributes';

	function admin_index() {
		$this->Attribute->recursive = 0;
		$this->set('attributes', $this->paginate());
	}

	function admin_edit($id = null) {
		if (!empty($this->data)) {
			$attributeGroup = $this->Attribute->AttributeGroup->read(null, $this->data['Attribute']['attribute_group_id']);
			$this->data['AttributeGroup'] = $attributeGroup['AttributeGroup'];
			
			if ($this->Attribute->save($this->data)) {
				$attrId = $this->Attribute->id;
				$tableName = Inflector::underscore(Inflector::pluralize($this->data['AttributeGroup']['model']));
				$fieldName = $this->data['Attribute']['code'];			
				# this is where you do all the magic
				$inputType = $this->data['Attribute']['input_type_id']; 
				# $fieldOptions = array('a', 'b', 'c');
				$fieldOptions = ''; 
				# this updates the database fields and column
				$this->Attribute->query($this->__buildQuery($this->data, $attrId));
				# this checks to see if a field name now exists in the table that matches input info
				$verified = $this->Attribute->query('SHOW columns FROM '.$tableName.' LIKE "'.$fieldName.'"');
				# now lets see if it worked 
				if (!empty($verified )) {
					$this->Session->setFlash(__('The Attribute has been saved', true));
					$this->redirect(array('action'=>'index'));
				} else {
					$this->Session->setFlash(__('The Attribute Db Update could not be saved. Please, try again.', true));
				}					
			} else {
				$this->Session->setFlash(__('The Attribute could not be saved. Please, try again.', true));
			}
		} else {
			if (!empty($id)) {
				$this->data = $this->Attribute->read(null, $id);
			}
			$attributeGroups = $this->Attribute->AttributeGroup->find('list');
			$this->set(compact('attributeGroups'));
		}
	}

	function admin_delete($id = null) {
		
		# To do, when you delete an attribute we need to delete the field from the database
		
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for Attribute', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Attribute->delete($id)) {
			$this->Session->setFlash(__('Attribute deleted', true));
			$this->redirect(array('action'=>'index'));
		}
	}
	
	
	function __buildQuery($thisData = null, $attrId = null) {
		pr($thisData);
		
		# this is where you do all the magic
		$tableName = Inflector::underscore(Inflector::pluralize($thisData['AttributeGroup']['model']));
		$fieldName = $thisData['Attribute']['code'];			
		$inputType = $thisData['Attribute']['input_type_id']; 
		# $fieldOptions = array('a', 'b', 'c');
		# un supported right now
		$fieldOptions = ''; 
				
		# set up the NULL value for the database field based on if its required or not
		if ($thisData['Attribute']['is_required'] == 1) {
			$nullStatus = ' NOT NULL ';
		} else {
			$nullStatus = ' NULL ';
		}
		
		# input the DEFAULT value if it exists
		if ($thisData['Attribute']['default_value'] == null) {
			$defaultStatus = ' ';
		} else {
			$defaultStatus = ' DEFAULT \''.$thisData['Attribute']['default_value'].'\' ';
		}
		
		
		# set up the UNIQUE index if it is a unique field
		if ($thisData['Attribute']['is_unique'] == 1) {
			$uniqueStatus = ' ADD UNIQUE (`'.$fieldName.'`) ';
		} else {
			$uniqueStatus = ' ';
		}
		
		
		/*
		$this->data['Attribute']['input_type_id'] values
		0 = 'Text Field'
		1 = 'Text Area'
		2 = 'Date'
		3 = 'Yes/No'
		4 = 'Multiple Select'
		5 = 'Dropdown'
		6 = 'Media/Image/File'
		*/
		if ($inputType == 0) {
			$fieldQuery = 'VARCHAR(255) '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 1) {
			$fieldQuery = 'TEXT '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 2) {
			$fieldQuery = 'DATETIME '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 3) {
			$fieldQuery = 'TINYINT(1) '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 4) {
			# this is multiple select and I'm not sure how to do this just yet
			$fieldQuery = 'TEXT '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 5) {
			$fieldQuery = 'ENUM("'.implode('", "',$fieldOptions).'") '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else if ($inputType == 6) {
			$fieldQuery = 'VARCHAR(255) '.$nullStatus.$defaultStatus.$uniqueStatus;
		} else {
			# break because its invalid
			$fieldQuery = false;
		}
		return $this->__query($tableName, $fieldName, $fieldQuery, $attrId);
	}
			
	function __query($tableName = null, $fieldName = null, $fieldQuery = null, $attrId = null) {
		#this will be a separate function
		$table = $this->Attribute->query('SHOW tables LIKE "'.$tableName.'"');
		if (!empty($table)) {
			# add to the existing table
			$query = 'ALTER TABLE `'.$tableName.'`';
			# finally add the field
			$query .= 'ADD `'.$fieldName.'` ' . $fieldQuery;
		} else {
			# add a new table	
			$query = 'CREATE TABLE `'.$tableName.'` ( `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY , ';
			# finally add the field
			$query .= '`'.$fieldName.'` ' .$fieldQuery. ');';
		}
		return $query;
	}

}
?>