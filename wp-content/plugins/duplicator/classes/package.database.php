<?php
if ( ! defined( 'DUPLICATOR_VERSION' ) ) exit; // Exit if accessed directly

class DUP_Database {
	
	//PUBLIC
	public $Type = 'MySQL';
	public $Size;
	public $File;
	public $Path;
	public $FilterTables;
	public $FilterOn;
	public $Name;
	public $Compatible;
	
	//PROTECTED
	protected $Package;
	
	//PRIVATE
	private $dbStorePath;
	private $EOFMarker;
	private $networkFlush;

	//CONSTRUCTOR
	function __construct($package) {
		 $this->Package = $package;
		 $this->EOFMarker = "";
		 $package_zip_flush  = DUP_Settings::Get('package_zip_flush');
		 $this->networkFlush = empty($package_zip_flush) ? false : $package_zip_flush;
	}
	
	public function Build($package) {
		try {
			
			$this->Package = $package;
			
			$time_start = DUP_Util::GetMicrotime();
			$this->Package->SetStatus(DUP_PackageStatus::DBSTART);
			$this->dbStorePath  = "{$this->Package->StorePath}/{$this->File}";
			
			$package_mysqldump	= DUP_Settings::Get('package_mysqldump');
			$package_phpdump_qrylimit = DUP_Settings::Get('package_phpdump_qrylimit');
			
			$mysqlDumpPath = self::GetMySqlDumpPath();
			$mode = ($mysqlDumpPath && $package_mysqldump) ? 'MYSQLDUMP' : 'PHP';
			$reserved_db_filepath = DUPLICATOR_WPROOTPATH . 'database.sql';

			
			$log  = "\n********************************************************************************\n";
			$log .= "DATABASE:\n";
			$log .= "********************************************************************************\n";
			$log .= "BUILD MODE:   {$mode}";
			$log .= ($mode == 'PHP') ? "(query limit - {$package_phpdump_qrylimit})\n" : "\n";
			$log .= "MYSQLTIMEOUT: " . DUPLICATOR_DB_MAX_TIME . "\n";
			$log .= "MYSQLDUMP:    ";
			$log .= ($mysqlDumpPath) ? "Is Supported" : "Not Supported";
			DUP_Log::Info($log);
			$log = null;
			
			//Reserved file found
			if (file_exists($reserved_db_filepath)) {
				DUP_Log::Error("Reserverd SQL file detected", 
						"The file database.sql was found at [{$reserved_db_filepath}].\n"
						. "\tPlease remove/rename this file to continue with the package creation.");
			}
			
			switch ($mode) {
				case 'MYSQLDUMP': $this->mysqlDump($mysqlDumpPath); 	break;
				case 'PHP' :	  $this->phpDump();	break;	
			}
			
			DUP_Log::Info("SQL CREATED: {$this->File}");
			$time_end = DUP_Util::GetMicrotime();
			$time_sum = DUP_Util::ElapsedTime($time_end, $time_start);
			
			//File below 10k will be incomplete
			$sql_file_size = filesize($this->dbStorePath);
			DUP_Log::Info("SQL FILE SIZE: " . DUP_Util::ByteSize($sql_file_size) . " ({$sql_file_size})");
			if ($sql_file_size < 10000) {
				DUP_Log::Error("SQL file size too low.", "File does not look complete.  Check permission on file and parent directory at [{$this->dbStorePath}]");
			}
			
			DUP_Log::Info("SQL FILE TIME: " . date("Y-m-d H:i:s"));
			DUP_Log::Info("SQL RUNTIME: {$time_sum}");
			
			$this->Size = @filesize($this->dbStorePath);
			$this->Package->SetStatus(DUP_PackageStatus::DBDONE);
			
		} catch (Exception $e) {
			DUP_Log::Error("Runtime error in DUP_Database::Build","Exception: {$e}");
		}
	}
	
	/**
	 *  Get the database stats
	 */
	public function Stats() {
		
		global $wpdb;
		$filterTables  = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
		$tblCount = 0;
	
		$tables	 = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
		$info = array();
		$info['Status']['Success'] = is_null($tables) ? false : true;
		$info['Status']['Size']    = 'Good';
		$info['Status']['Rows']    = 'Good';
		
		$info['Size']   = 0;
		$info['Rows']   = 0;
		$info['TableCount'] = 0;
		$info['TableList']	= array();
		
		//Only return what we really need
		foreach ($tables as $table) {
			
			$name = $table["Name"];
			if ($this->FilterOn  && is_array($filterTables)) {
				if (in_array($name, $filterTables)) {
					continue;
				}
			}
			$size = ($table["Data_length"] +  $table["Index_length"]);
			
			$info['Size'] += $size;
			$info['Rows'] += ($table["Rows"]);
			$info['TableList'][$name]['Rows']	= empty($table["Rows"]) ? '0' : number_format($table["Rows"]);
			$info['TableList'][$name]['Size']	= DUP_Util::ByteSize($size);
			$tblCount++;
		}
		
		$info['Status']['Size']   = ($info['Size'] > 100000000) ? 'Warn' : 'Good';
		$info['Status']['Rows']   = ($info['Rows'] > 1000000)   ? 'Warn' : 'Good';
		$info['TableCount']		  = $tblCount;
		
		return $info;
	}	

	/**
	 * Returns the mysqldump path if the server is enabled to execute it
	 * @return boolean|string
	 */	
	public static function GetMySqlDumpPath() {
		
		//Is shell_exec possible
		if (! DUP_Util::IsShellExecAvailable()) {
			return false;
		}

		$custom_mysqldump_path	= DUP_Settings::Get('package_mysqldump_path');
		$custom_mysqldump_path = (strlen($custom_mysqldump_path)) ? $custom_mysqldump_path : '';
		
		//Common Windows Paths
		if (DUP_Util::IsOSWindows()) {
			$paths = array(
				$custom_mysqldump_path,
				'C:/xampp/mysql/bin/mysqldump.exe',
				'C:/Program Files/xampp/mysql/bin/mysqldump',
				'C:/Program Files/MySQL/MySQL Server 6.0/bin/mysqldump',
				'C:/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump',
				'C:/Program Files/MySQL/MySQL Server 5.4/bin/mysqldump',
				'C:/Program Files/MySQL/MySQL Server 5.1/bin/mysqldump',
				'C:/Program Files/MySQL/MySQL Server 5.0/bin/mysqldump',
			);	
			
		//Common Linux Paths			
		} else {
			$path1 = '';
			$path2 = '';
			$mysqldump = `which mysqldump`;
			if (@is_executable($mysqldump)) 
				$path1 = (!empty($mysqldump)) ? $mysqldump : '';
			
			$mysqldump = dirname(`which mysql`) . "/mysqldump";
			if (@is_executable($mysqldump)) 
				$path2 = (!empty($mysqldump)) ? $mysqldump : '';
			
			$paths = array(
				$custom_mysqldump_path,
				$path1,
				$path2,
				'/usr/local/bin/mysqldump',
				'/usr/local/mysql/bin/mysqldump',
				'/usr/mysql/bin/mysqldump',
				'/usr/bin/mysqldump',
				'/opt/local/lib/mysql6/bin/mysqldump',
				'/opt/local/lib/mysql5/bin/mysqldump',
				'/opt/local/lib/mysql4/bin/mysqldump',
			);
		}

		// Find the one which works
		foreach ( $paths as $path ) {
		    if ( @is_executable($path))
	 	    	return $path;
		}
		
		return false;
	}

	
	private function mysqlDump($exePath) {
		
		global $wpdb;
		
		$host = explode(':', DB_HOST);
		$host = reset($host);
		$port = strpos(DB_HOST, ':') ? end(explode( ':', DB_HOST ) ) : '';
		$name = DB_NAME;
		$mysqlcompat_on  = isset($this->Compatible) && strlen($this->Compatible);
		
		//Build command
		$cmd = escapeshellarg($exePath);
		$cmd .= ' --no-create-db';
		$cmd .= ' --single-transaction';
		$cmd .= ' --hex-blob';
		$cmd .= ' --skip-add-drop-table';
		
		//Compatibility mode
		if ($mysqlcompat_on) {
			DUP_Log::Info("COMPATIBLE: [{$this->Compatible}]");	
			$cmd .= " --compatible={$this->Compatible}";	
		}
		
		//Filter tables
		$tables			= $wpdb->get_col('SHOW TABLES');
		$filterTables	= isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
		$tblAllCount	= count($tables);
		$tblFilterOn	= ($this->FilterOn) ? 'ON' : 'OFF';

		if (is_array($filterTables) && $this->FilterOn) {
			foreach ($tables as $key => $val) {
				if (in_array($tables[$key], $filterTables)) {
					$cmd .= " --ignore-table={$name}.{$tables[$key]} ";
					unset($tables[$key]);
				}
			}
		}
		
		$cmd .= ' -u ' . escapeshellarg(DB_USER);
		$cmd .= (DB_PASSWORD) ? 
				' -p'  . escapeshellarg(DB_PASSWORD) : '';
		$cmd .= ' -h ' . escapeshellarg($host);
		$cmd .= ( ! empty($port) && is_numeric($port) ) ?
				' -P ' . $port : '';
		$cmd .= ' -r ' . escapeshellarg($this->dbStorePath);
		$cmd .= ' ' . escapeshellarg(DB_NAME);
		$cmd .= ' 2>&1';		

		$output = shell_exec($cmd);

		// Password bug > 5.6 (@see http://bugs.mysql.com/bug.php?id=66546)
		if ( trim( $output ) === 'Warning: Using a password on the command line interface can be insecure.' ) {
			$output = '';
		}
		$output = (strlen($output)) ? $output : "Ran from {$exePath}";
		
		$tblCreateCount = count($tables);
		$tblFilterCount = $tblAllCount - $tblCreateCount;
		
		//DEBUG
		//DUP_Log::Info("COMMAND: {$cmd}");
		DUP_Log::Info("FILTERED: [{$this->FilterTables}]");	
		DUP_Log::Info("RESPONSE: {$output}");
		DUP_Log::Info("TABLES: total:{$tblAllCount} | filtered:{$tblFilterCount} | create:{$tblCreateCount}");
	
		$sql_footer  = "\n\n/* Duplicator WordPress Timestamp: " . date("Y-m-d H:i:s") . "*/\n";
		$sql_footer .= "/* " . DUPLICATOR_DB_EOF_MARKER . " */\n";
		file_put_contents($this->dbStorePath, $sql_footer, FILE_APPEND);
	
		return ($output) ?  false : true;
	}


	private function phpDump() {

		global $wpdb;

		$wpdb->query("SET session wait_timeout = " . DUPLICATOR_DB_MAX_TIME);
		$handle		= fopen($this->dbStorePath, 'w+');
		$tables		= $wpdb->get_col('SHOW TABLES');

		$filterTables   = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
		$tblAllCount	= count($tables);
		$tblFilterOn	= ($this->FilterOn) ? 'ON' : 'OFF';
		$qryLimit	    = DUP_Settings::Get('package_phpdump_qrylimit');

		if (is_array($filterTables) && $this->FilterOn) {
			foreach ($tables as $key => $val) {
				if (in_array($tables[$key], $filterTables)) {
					unset($tables[$key]);
				}
			}
		}
		$tblCreateCount = count($tables);
		$tblFilterCount = $tblAllCount - $tblCreateCount;

		DUP_Log::Info("TABLES: total:{$tblAllCount} | filtered:{$tblFilterCount} | create:{$tblCreateCount}");
		DUP_Log::Info("FILTERED: [{$this->FilterTables}]");	

		$sql_header = "/* DUPLICATOR MYSQL SCRIPT CREATED ON : " . @date("Y-m-d H:i:s") . " */\n\n";
		$sql_header .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
		fwrite($handle, $sql_header);

		//BUILD CREATES:
		//All creates must be created before inserts do to foreign key constraints
		foreach ($tables as $table) {
			//$sql_del = ($GLOBALS['duplicator_opts']['dbadd_drop']) ? "DROP TABLE IF EXISTS {$table};\n\n" : "";
			//@fwrite($handle, $sql_del);
			$create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
			@fwrite($handle, "{$create[1]};\n\n");
		}

		//BUILD INSERTS: 
		//Create Insert in 100 row increments to better handle memory
		foreach ($tables as $table) {

			$row_count = $wpdb->get_var("SELECT Count(*) FROM `{$table}`");
			//DUP_Log::Info("{$table} ({$row_count})");

			if ($row_count > $qryLimit) {
				$row_count = ceil($row_count / $qryLimit);
			} else if ($row_count > 0) {
				$row_count = 1;
			}

			if ($row_count >= 1) {
				fwrite($handle, "\n/* INSERT TABLE DATA: {$table} */\n");
			}

			for ($i = 0; $i < $row_count; $i++) {
				$sql = "";
				$limit = $i * $qryLimit;
				$query = "SELECT * FROM `{$table}` LIMIT {$limit}, {$qryLimit}";
				$rows = $wpdb->get_results($query, ARRAY_A);
				if (is_array($rows)) {
					foreach ($rows as $row) {
						$sql .= "INSERT INTO `{$table}` VALUES(";
						$num_values = count($row);
						$num_counter = 1;
						foreach ($row as $value) {
							if ( is_null( $value ) || ! isset( $value ) ) {
								($num_values == $num_counter) 	? $sql .= 'NULL' 	: $sql .= 'NULL, ';
							} else {
								($num_values == $num_counter) 
									? $sql .= '"' . @esc_sql($value) . '"' 
									: $sql .= '"' . @esc_sql($value) . '", ';
							}
							$num_counter++;
						}
						$sql .= ");\n";
					}
					fwrite($handle, $sql);
				}
			}
			
			//Flush buffer if enabled
			if ($this->networkFlush) {
				DUP_Util::FcgiFlush();
			}
			$sql = null;
			$rows = null;
		}
		
		$sql_footer = "\nSET FOREIGN_KEY_CHECKS = 1; \n\n";
		$sql_footer .= "/* Duplicator WordPress Timestamp: " . date("Y-m-d H:i:s") . "*/\n";
		$sql_footer .= "/* " . DUPLICATOR_DB_EOF_MARKER . " */\n";
		fwrite($handle, $sql_footer);
		$wpdb->flush();
		fclose($handle);
	}
}
?>