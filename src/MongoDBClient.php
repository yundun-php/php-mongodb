<?php

namespace MongoDB;

use \Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Exceptions\MongoDBClientException;
use MongoDB\BSON\UTCDateTime;


class MongoDBClient {

	public static $config;

	public static $instance;

	public static $logger;

	public $client;

	public $collection;

	public static function setConfig( $config ) {
		self::$config = $config;
	}

	public static function setLogger( $logger ) {
		self::$logger = $logger;
	}

	private function __construct() {
		$uri           = isset( self::$config['uri'] ) ? self::$config['uri'] : 'mongodb://127.0.0.1:27017/';
		$uriOptions    = isset( self::$config['uriOptions'] ) ? self::$config['uriOptions'] : [];
		$driverOptions = isset( self::$config['driverOptions'] ) ? self::$config['driverOptions'] : [];
		$this->client  = new Client( $uri, $uriOptions, $driverOptions );
		if ( isset( self::$config['db'] ) && isset( self::$config['col'] ) && $this->client ) {
			$options          = isset( self::$config['options'] ) ? self::$config['options'] : [];
			$this->collection = $this->client->selectCollection( self::$config['db'], self::$config['col'], $options );
		}
	}

	private function __clone() {
	}

	/**
	 * @param string $key
	 *
	 * @return static
	 */
	public static function getInstance( $key = 'default' ) {
		try {
			if ( ! isset( self::$instance[ $key ] ) ) {
				self::$instance[ $key ] = new self();
			}

			return self::$instance[ $key ];
		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "get instance exception:" . $e->getMessage() );
			}

			return null;
		}
	}


	public function __call( $name, $arguments ) {
		try {
			if ( is_null( $this->client ) ) {
				if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
					self::$logger->error( "not connect to mongo" );
				}
				throw new MongoDBClientException( "not connect to mongo" );
			}

			if ( ! method_exists( $this->client, $name ) ) {
				if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
					self::$logger->error( "not find method $name" );
				}
				throw new MongoDBClientException( "not find method $name" );
			}

			$result = call_user_func_array( [ $this->client, $name ], $arguments );

			return $result;
		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "call function $name  : " . $e->getMessage() );
			}
			if ( isset( self::$config['throw'] ) && self::$config['throw'] ) {
				throw $e;
			}

			return false;
		}
	}

	public function setCollection( $collection ) {
		$this->collection = $collection;
	}

	public function getCollection() {
		if ( is_null( $this->collection ) ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "collection is null" );
			}
			throw new MongoDBClientException( "collection is null, please use selectCollection function to setCollection or set config use col key" );
		}

		return $this->collection;
	}

	public function getCurrentTimeUTC() {
		$utcTime = new UTCDateTime( time() * 1000 );

		return $utcTime;
	}

	public function getCurrentTimeMicroUTC() {
		$utcTime = new UTCDateTime( ( microtime( true ) * 1000 ) );

		return $utcTime;
	}

	public function formatTimeStampToUTC( $time ) {
		$utcTime = new UTCDateTime( $time * 1000 );

		return $utcTime;
	}

	public function formatDateTimeToUTC( $dateTime ) {
		$time    = strtotime( $dateTime );
		$utcTime = new UTCDateTime( $time * 1000 );

		return $utcTime;
	}


	public function addOne( $document, $options = [] ) {
		try {
			if ( ! isset( $document['created_at'] ) ) {
				$document['created_at']        = $this->getCurrentTimeUTC();
				$document['created_at_format'] = date( 'Y-m-d H:i:s' );
			}
			if ( ! isset( $document['updated_at'] ) ) {
				$document['updated_at']        = $this->getCurrentTimeUTC();
				$document['updated_at_format'] = date( 'Y-m-d H:i:s' );
			}
			$insertOneResult = $this->getCollection()->insertOne( $document, $options );

			$result = [
				'count' => $insertOneResult->getInsertedCount(),
				'id'    => (string) $insertOneResult->getInsertedId(),
				'ack'   => $insertOneResult->isAcknowledged(),
			];

			return $result;

		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "addOne error:" . $e->getMessage() );
			}
			if ( isset( self::$config['throw'] ) && self::$config['throw'] ) {
				throw $e;
			}

			return false;
		}
	}


	public function updateOne( $filter, $update, array $options = [] ) {
		try {
			$updateOneResult = $this->getCollection()->updateOne( $filter, $update, $options );

			$result = [
				'modifiedCount' => $updateOneResult->getModifiedCount(),
				'upsertedCount' => $updateOneResult->getUpsertedCount(),
				'upsertedId'    => (string) $updateOneResult->getUpsertedId(),
				'ack'           => $updateOneResult->isAcknowledged(),
			];

			return $result;
		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "updateOne error:" . $e->getMessage() );
			}
			if ( isset( self::$config['throw'] ) && self::$config['throw'] ) {
				throw $e;
			}

			return false;
		}
	}

	public function updateOneByObjectId( $id, $updateData, $options = [ 'upsert' => true ] ) {
		$_id    = $id instanceof ObjectId ? $id : new ObjectId( $id );
		$filter = [
			'_id' => $_id,
		];
		if ( ! isset( $updateData['updated_at'] ) ) {
			$updateData['updated_at']        = $this->getCurrentTimeUTC();
			$updateData['updated_at_format'] = date( 'Y-m-d H:i:s' );
		}
		$update = [ '$set' => $updateData ];

		return $this->updateOne( $filter, $update, $options );
	}

	public function updateOneByPk( $pkValue, $updateData, $pkField = '_id', $options = [ 'upsert' => true ] ) {
		//attention pkValue type may be string or number
		$filter = [
			$pkField => $pkValue,
		];
		if ( ! isset( $updateData['updated_at'] ) ) {
			$updateData['updated_at']        = $this->getCurrentTimeUTC();
			$updateData['updated_at_format'] = date( 'Y-m-d H:i:s' );
		}
		$update = [ '$set' => $updateData ];

		return $this->updateOne( $filter, $update, $options );
	}


	public function replaceOne( $filter, $replacement, array $options = [] ) {
		try {
			$replaceOneResult = $this->getCollection()->replaceOne( $filter, $replacement, $options );

			$result = [
				'upsertedId'    => (string) $replaceOneResult->getUpsertedId(),
				'upsertedCount' => $replaceOneResult->getUpsertedCount(),
				'modifiedCount' => $replaceOneResult->getModifiedCount(),
				'matchedCount'  => $replaceOneResult->getMatchedCount(),
				'ack'           => $replaceOneResult->isAcknowledged(),
			];

			return $result;
		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "replaceOne error:" . $e->getMessage() );
			}
			if ( isset( self::$config['throw'] ) && self::$config['throw'] ) {
				throw $e;
			}

			return false;
		}
	}

	public function deleteOne( $filter, array $options = [] ) {
		try {
			$deleteOneResult = $this->getCollection()->deleteOne( $filter, $options );

			$result = [
				'deleteCount' => $deleteOneResult->getDeletedCount(),
				'ack'         => $deleteOneResult->isAcknowledged(),
			];

			return $result;

		} catch ( Exception $e ) {
			if ( self::$logger && isset( self::$config['log'] ) && self::$config['log'] ) {
				self::$logger->error( "deleteOne error:" . $e->getMessage() );
			}
			if ( isset( self::$config['throw'] ) && self::$config['throw'] ) {
				throw $e;
			}

			return false;
		}
	}


}