<?php


namespace Tests;


use MongoDB\MongoDBClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use MongoDB\BSON\ObjectId;

class MongoDBClientTest extends TestCase {


	public function testMongoOp() {
		$config = [
			'uri'   => 'mongodb://admin:admin@127.0.0.1:27017',
			'db'    => 'test_client_db',
			'col'   => 'test_client_col',
			'log'   => 1, //记录日志
			'throw' => 1, //抛异常
		];
		MongoDBClient::setConfig( $config );
		$logger = new Logger( 'my_logger' );
		$logger->pushHandler( new StreamHandler( __DIR__ . '/my_app.log', Logger::DEBUG ) );
		MongoDBClient::setLogger( $logger );

		$result  = MongoDBClient::getInstance()->getCurrentTimeUTC();
		$result1 = MongoDBClient::getInstance()->formatDateTimeToUTC( date( 'Y-m-d H:i:s' ) );
		$result2 = MongoDBClient::getInstance()->formatTimeStampToUTC( time() );
		$result3 = MongoDBClient::getInstance()->getCurrentTimeMicroUTC();
		var_dump( $result, $result1, $result2, $result3 );

		//list database
		$result4 = MongoDBClient::getInstance()->listDatabases();
		var_dump( $result4 );

		//insert
		$plan_setting_json = '{
    "meal": {
        "service_bandwidth": {
            "title": "业务带宽",
            "show": "业务带宽",
            "value": "100",
            "unit": "Mbps\/月"
        },
        "basic_safety_protection": {
            "title": "基础安全防护",
            "value": 1
        },
        "advanced_safety_protection": {
            "title": "高级安全防护",
            "value": 1
        },
        "data_analysis": {
            "title": "基础分析",
            "value": 1
        },
        "performance_acceleration": {
            "title": "性能加速",
            "value": 1
        },
        "alarm_service": {
            "title": "告警服务",
            "value": 1
        },
        "cloud_speedUp_flow": {
            "title": "云加速流量",
            "show": "云加速流量",
            "value": "2048",
            "unit": "G \/ 月"
        },
        "fw_domain_rule_total": {
            "title": "精准访问控制（7层）",
            "show": "精准访问控制（7层）",
            "value": "100",
            "unit": "条\/域名"
        },
        "meal_price": {
            "title": "套餐价格",
            "price": "11300",
            "unit": "¥",
            "count_unit": "num",
            "time_unit": "m",
            "min_buy": "",
            "origin_price": "11300",
            "base_multiple": "1",
            "count_type": "one_to_one",
            "config": []
        },
        "rule_number_domain": {
            "title": "域名条数",
            "show": "域名条数",
            "value": 10,
            "unit": "个"
        },
        "rule_number_pridomain": {
            "title": "主域名条数",
            "show": "主域名条数",
            "value": "1",
            "unit": "个"
        },
        "rule_number_subdomain": {
            "title": "子域名条数",
            "show": "子域名条数",
            "value": "9",
            "unit": "个"
        },
        "rule_number_total_domain": {
            "title": "总域名条数",
            "show": "总域名条数",
            "value": "10",
            "unit": "个"
        },
        "default_fields": {
            "buy_time": {
                "title": "购买时长",
                "unit": [
                    "d",
                    "m",
                    "y"
                ],
                "value": "1",
                "readonly": "0"
            }
        },
        "backstage_show": {
            "default_fields": [
                "buy_time"
            ],
            "meal_operate": [
                "kuorong",
                "uplevel"
            ]
        },
        "meal_id": "971",
        "level": "2",
        "meal_name": "商业版过渡",
        "meal_flag": "YD-WAFSYB-GD",
        "product_id": "26",
        "product_flag": "HW",
        "product_name": "红网卫士"
    },
    "kuorong": {
        "domain_packet": {
            "title": "域名数",
            "price_type": "one_to_one",
            "price": 1500,
            "price_num_unit": 10,
            "limit_min": 0,
            "limit_max": 100,
            "limit_beishu": 10,
            "default": 0
        },
        "fw_domain_rule_total": {
            "title": "精准化访问控制",
            "price_type": "one_to_one",
            "price": 500,
            "price_num_unit": 100,
            "limit_min": 0,
            "limit_beishu": 100,
            "default": 0
        }
    },
    "zengzhi": [],
    "cfg": {
        "meal": {
            "buy_num": 1,
            "price_level": 0,
            "unit": ""
        },
        "kuorong": [],
        "zengzhi": []
    }
}';
		$document          = [
			'field1'            => 1,
			'field2'            => 'test2',
			'plan_setting'      => json_decode( $plan_setting_json, 1 ),
			'plan_setting_json' => $plan_setting_json,
		];
		$result            = MongoDBClient::getInstance()->addOne( $document );
		var_dump( 'add', $result );

		//update
		$filter  = [
			'_id' => new ObjectId( '5f3b9a80aab9444a8a1deaa4' ),
		];
		$update  = [ '$set' => [ 'field2' => 22, 'create_at_format' => 223, 'field3' => 33, 'field4' => 4 ] ];
		$options = [
			'upsert' => true,
		];
		$result  = MongoDBClient::getInstance()->updateOne( $filter, $update, $options );
		var_dump( 'update', $result );

		//update by id
		$id         = '5f3b9a80aab9444a8a1deaa4';
		$updateData = [ 'field2' => 22, 'create_at_format' => 223, 'field3' => 3, 'field4' => 5, 'field5' => 5 ];
		$result     = MongoDBClient::getInstance()->updateOneByObjectId( $id, $updateData );
		var_dump( 'updateOneByObjectId', $result );

		//update by pk
		$id         = 4;
		$updateData = [ 'field2' => 22, 'create_at_format' => 223, 'field3' => 3, 'field4' => 5, 'field5' => 5 ];
		$result     = MongoDBClient::getInstance()->updateOneByPk( $id, $updateData );
		var_dump( 'updateOneByPk', $result );

		//delete
		$filter  = [
			'_id' => new ObjectId( '5f3b9a80aab9444a8a1deaa5' ),
		];
		$options = [
		];
		$result  = MongoDBClient::getInstance()->deleteOne( $filter, $options );
		var_dump( 'delete', $result );

	}


}