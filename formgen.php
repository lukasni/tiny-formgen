<?php
error_reporting(0);
if (array_key_exists('dbhost', $_POST)) {
	try {
		$pdo = new PDO('mysql:host='.$_POST['dbhost'].';dbname='.$_POST['dbname'], $_POST['dbuser'], $_POST['dbpass']);
		$result = $pdo->query('DESCRIBE '.$_POST['dbtable'])->fetchAll(PDO::FETCH_ASSOC);
		$output = '<form action="" method="post">'."\n";
		foreach($result as $field) 
			$output .= parseField($field);
		$output .= '<button type="submit">Submit</button>'."\n".'</form>';
	} catch (Exception $e) {
		die('You done fucked up, mate: '.$e->getMessage());
	}
}
function parseField(array $field) {
	if (preg_match('/auto_increment/', $field['Extra']) ) return '';
	$data= [
		'tag' => 'input',
		'attributes' => ' name="'.$field['Field'].'" id="'.$field['Field'].'"',
	];
	preg_match('/(?P<type>\w+)($|\((?P<params>(\d+|(.*)))\))/', $field['Type'], $type);
	if ( preg_match('/(int|float|double|decimal|bit|boolean)/', $type['type']) ) {
		if ( ($type['type'] == 'tinyint' && $type['params'] == '1') || $type['type'] == 'boolean') {
			$data['attributes'] .= ' type="checkbox"';
		} else {
			$data['attributes'] .= ' type="number"';
			if ($type['type'] == 'float' || $type['type'] == 'dobule' || $type['type'] == 'decimal')
				$data['attributes'] .= ' step="any"';
		}
	} else if ( preg_match('/(enum|set)/', $type['type']) ) {
		$data['options'] = str_getcsv($type['params'], ',', "'");
		if ( count($data['options']) < 4 ) {
			$data['attributes'] = ($type['type'] == 'enum') ? ' type="radio"' : ' type="checkbox"';
		} else {
			$data['tag'] = 'select';
			$data['attributes'] .= ($type['type'] == 'enum') ? '' : ' multiple';
		}
	} else if ( preg_match('/(text)/', $type['type']) ) {
		$data['tag'] = 'textarea';
	} else if (preg_match('/(date|timestamp)/', $type['type'])) {
		$data['attributes'] .= ' type="datetime-local"';
	} else {
		$data['attributes'].= preg_match('/(password|passwort)/', $field['Field']) ?' type="password"' :' type="text"';
		$data['attributes'].= is_numeric($type['params']) ? ' maxlength="'.$type['params'].'"' : '';
	}
	$data['attributes'] .= ($field['Null'] == 'NO') ? ' required' : '';
	if ( array_key_exists('options', $data) ) {
		if ( $data['tag'] == 'input') {
			$name = ($type['type'] == 'enum') ? $field['Field'] : $field['Field'].'[]';
			$result = '<span>'.$field['Field']."</span><br>\n";
			foreach ($data['options'] as $o) {
				$result .= '<label for='.$field['Field'].'_'.$o.'">'.$o.'</label>';
				$result .= '<'.$data['tag'].$data['attributes'];
				$result .= ' name="'.$name.'"" id="'.$field['Field'].'_'.$o.'"><br>'."\n";
			}
		} else {
			$result  = '<label for='.$field['Field'].'">'.$field['Field'].'</label>';
			$result .= '<'.$data['tag'].$data['attributes'].'>'."\n";
			foreach ($data['options'] as $o)
				$result .= "\t<option>$o</option>\n";
			$result .= "</select><br>\n";
		}
	} else {
		$result  = '<label for='.$field['Field'].'">'.$field['Field'].'</label>';
		$result .= '<'.$data['tag'].$data['attributes'].'>';
		$result .= ($data['tag'] == 'textarea') ? "</textarea><br>\n" : "<br>\n";
	}
	return $result;
}?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Tiny Form Generator</title>
	<style type="text/css">
label { 
	display: inline-block;
	width: 150px; 
}
	</style>
</head>
<body>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<label for="dbhost">DB Host
	<input type="text" name="dbhost"></label><br>
	<label for="dbuser">DB User
	<input type="text" name="dbuser"></label><br>
	<label for="dbpass">DB Pass
	<input type="password" name="dbpass"></label><br>
	<label for="dbschema">Database
	<input type="text" name="dbname"></label><br>
	<label for="dbtable">Table
	<input type="text" name="dbtable"></label><br>
	<button type="submit">Generate Form</button>
</form>
<h2>Output:</h2>
<?=isset($output)?$output:'Not generated';?>
</body>
</html>