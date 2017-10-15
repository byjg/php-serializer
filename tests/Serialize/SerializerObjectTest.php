<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use stdClass;
use Tests\Sample\ModelForceProperty;
use Tests\Sample\ModelGetter;
use Tests\Sample\ModelList;
use Tests\Sample\ModelList2;
use Tests\Sample\ModelList3;
use Tests\Sample\ModelPublic;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class SerializerObjectTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    public function testCreateObjectFromModel_ObjectGetter_1elem()
    {
        $model = new ModelGetter(10, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );

        $this->assertEquals(
            '{"Id":10,"Name":"Joao"}',
            (new JsonFormatter())->process($result)
        );

        $this->assertEquals(
            "10\nJoao\n",
            (new PlainTextFormatter())->process($result)
        );

        $this->assertEquals(
            "<div>10</div><div>Joao</div>",
            (new PlainTextFormatter("</div>", "<div>"))->process($result)
        );

        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><Id>10</Id><Name>Joao</Name></root>\n",
            (new XmlFormatter())->process($result)
        );

    }

    public function testCreateObjectFromModel_ObjectGetter_2elem()
    {
        $model = array(
            new ModelGetter(10, 'Joao'),
            new ModelGetter(20, 'JG')
        );

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                ['Id' => 10, 'Name' => 'Joao'],
                ['Id' => 20, 'Name' => 'JG']
            ]
            , $result
        );

        $this->assertEquals(
            '[{"Id":10,"Name":"Joao"},{"Id":20,"Name":"JG"}]',
            (new JsonFormatter())->process($result)
        );

        $this->assertEquals(
            "10\nJoao\n\n20\nJG\n\n",
            (new PlainTextFormatter())->process($result)
        );

        $this->assertEquals(
            "<div><div>10</div><div>Joao</div></div><div><div>20</div><div>JG</div></div>",
            (new PlainTextFormatter("</div>", "<div>"))->process($result)
        );

        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><item0><Id>10</Id><Name>Joao</Name></item0><item1><Id>20</Id><Name>JG</Name></item1></root>\n",
            (new XmlFormatter())->process($result)
        );

    }

    public function testCreateObjectFromModel_ObjectPublic_1elem()
    {
        $model = new ModelPublic(10, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_ObjectPublic_2elem()
    {
        $model = array(
            new ModelPublic(10, 'Joao'),
            new ModelPublic(20, 'JG')
        );

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                ['Id' => 10, 'Name' => 'Joao'],
                ['Id' => 20, 'Name' => 'JG']
            ]
            , $result
        );
    }

    public function testCreateObjectFromModel_StdClass_1()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_StdClass_Model()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object = new ModelGetter(20, 'JG');

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao', 'Object' => ['Id' => 20, 'Name'=>'JG']],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_1()
    {
        $model = [
            'Id' => 10,
            'Name' => 'Joao'
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_2()
    {
        $model = [
            'Id' => 10,
            'Name' => 'Joao',
            'Data' =>
            [
                'Code' => '2',
                'Sector' => '3'
            ]
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'Id' => 10,
                'Name' => 'Joao',
                'Data' =>
                    [
                        'Code' => '2',
                        'Sector' => '3'
                    ]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_StdClass_Array()
    {
        $model = new stdClass();
        $model->Obj = [
            'Id' => 10,
            'Name' => 'Joao'
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                "Obj" => ['Id' => 10, 'Name' => 'Joao']
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Scalar()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao'
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ["Obj" => [10, 'Joao']],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Mixed()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            new ModelGetter(20, 'JG')
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ["Obj" => [10, 'Joao', ['Id'=>20, 'Name'=>'JG']]],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array()
    {
        $model = new stdClass();
        $model->Obj = [
            'Item1' =>
            [
                10,
                'Joao'
            ],
            'Item2' =>
            [
                20,
                'JG'
            ]
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'Obj' => [
                    'Item1' =>
                        [
                            10,
                            'Joao'
                        ],
                    'Item2' =>
                        [
                            20,
                            'JG'
                        ]
                ]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_2()
    {
        $model = new stdClass();
        $model->Obj = [
            [
                10,
                'Joao'
            ]
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'Obj' => [
                    [
                        10,
                        'Joao'
                    ]
                ]
            ], $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_3()
    {
        $model = [
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            [
                'Id' => 11,
                'Name' => 'Gilberto'
            ],
        ];

        $object = new SerializerObject($model);

        $result = $object->build();

        $this->assertEquals(
            [
                [
                    'Id' => 10,
                    'Name' => 'Joao'
                ],
                [
                    'Id' => 11,
                    'Name' => 'Gilberto'
                ],
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_5()
    {
        $model = new stdClass;

        $model->Title = 'testing';
        $model->List = [
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            [
                'Id' => 11,
                'Name' => 'Gilberto'
            ],
        ];
        $model->Group = "test";

        $object = new SerializerObject($model);

        $result = $object->build();

        $this->assertEquals(
            [
                'Title' => 'testing',
                'List' => [
                    [
                        'Id' => 10,
                        'Name' => 'Joao'
                    ],
                    [
                        'Id' => 11,
                        'Name' => 'Gilberto'
                    ]
                ],
                "Group" => "test"
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_DontCreateNode()
    {
        $modellist = new ModelList();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = new SerializerObject($modellist);
        $result = $object->build();

        $this->assertEquals(
            [
                "collection" =>
                [
                    ["Id"=>10, "Name"=>"Joao"],
                    ["Id"=>20, "Name"=>"JG"]
                ]
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_CreateNode()
    {
        $modellist = new ModelList2();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = new SerializerObject($modellist);
        $result = $object->build();

        $this->assertEquals(
            [
                "collection" =>
                    [
                        ["Id"=>10, "Name"=>"Joao"],
                        ["Id"=>20, "Name"=>"JG"]
                    ]
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_SkipParentAndRenameChild()
    {
        $modellist = new ModelList3();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = new SerializerObject($modellist);
        $result = $object->build();

        $this->assertEquals(
            [
                "collection" =>
                    [
                        ["Id"=>10, "Name"=>"Joao"],
                        ["Id"=>20, "Name"=>"JG"]
                    ]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_OnlyScalarAtFirstLevel()
    {
        $model = [
            10,
            'Joao'
        ];

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                10,
                'Joao'
            ],
            $result
        );
    }

    public function testEmptyValues()
    {
        $model = new stdClass();
        $model->varFalse = false;
        $model->varTrue = true;
        $model->varZero = 0;
        $model->varZeroStr = '0';
        $model->varNull = null;
        $model->varEmptyString = '';

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'varFalse' => false,
                'varTrue' => true,
                'varZero' => 0,
                'varZeroStr' => '0',
                'varNull' => null,
                'varEmptyString' => ''
            ],
            $result
        );

        $object->setOnlyString(true)->build();

        $this->assertEquals(
            [
                'varFalse' => '',
                'varTrue' => '1',
                'varZero' => '0',
                'varZeroStr' => '0',
                'varNull' => '',
                'varEmptyString' => ''
            ],
            $result
        );

        $result = $object->setOnlyString(false)->setBuildNull(false)->build();

        $this->assertEquals(
            [
                'varFalse' => false,
                'varTrue' => true,
                'varZero' => 0,
                'varZeroStr' => '0',
                'varEmptyString' => ''
            ],
            $result
        );

    }

    public function testEmptyValues_2()
    {
        $model = new ModelPublic(null, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->setBuildNull(false)->build();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );

        $model = new ModelPublic(null, null);
        $object = new SerializerObject($model);

        $result = $object->setBuildNull(false)->build();

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testEmptyValues_3()
    {
        $model = new ModelGetter(null, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->setBuildNull(false)->build();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );
    }

    public function testEmptyValues_4()
    {
        $model = new ModelList();

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'collection' => null
            ],
            $result
        );

        $result = $object->setBuildNull(false)->build();

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testEmptyValues_5()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(null, 'Joao'));
        $model->addItem(new ModelGetter(null, null));

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                'collection' => [
                    [
                        'Id' => null,
                        'Name' => 'Joao'
                    ],
                    [
                        'Id' => null,
                        'Name' => null
                    ]
                ]
            ],
            $result
        );

        $result = $object->setBuildNull(false)->build();

        $this->assertEquals(
            [
                'collection' => [
                    [
                        'Name' => 'Joao'
                    ],
                    [
                    ]
                ]
            ],
            $result
        );
    }

    public function testFirstLevel()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object = new ModelGetter(20, 'JG');

        $object = new SerializerObject($model);
        $object->setStopFirstLevel(true);
        $result = $object->build();

        $this->assertEquals(
            ["Id" => 10, "Name" => 'Joao', 'Object' => new ModelGetter(20, 'JG')],
            $result
        );

    }

    public function testFirstLevel_2()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            new ModelGetter(20, 'JG')
        ];

        $object = new SerializerObject($model);
        $object->setStopFirstLevel(true);
        $result = $object->build();

        $this->assertEquals(
            ["Obj" => [10, 'Joao', new ModelGetter(20, 'JG')]],
            $result
        );
    }

    public function testDoNotParseClass()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object1 = new ModelGetter(20, 'JG');
        $model->Object2 = new ModelPublic(10, 'JG2');

        $object = new SerializerObject($model);
        $object->setDoNotParse([
            ModelPublic::class
        ]);
        $result = $object->build();

        $this->assertEquals(
            [
                "Id" => 10,
                "Name" => 'Joao',
                'Object1' => ['Id' => 20, 'Name' => 'JG'],
                'Object2' => new ModelPublic(10, 'JG2')
            ],
            $result
        );

        $object2 = new SerializerObject($model);
        $object2->setDoNotParse([
            ModelPublic::class,
            ModelGetter::class
        ]);
        $result = $object2->build();

        $this->assertEquals(
            [
                "Id" => 10,
                "Name" => 'Joao',
                'Object1' => new ModelGetter(20, 'JG'),
                'Object2' => new ModelPublic(10, 'JG2')
            ],
            $result
        );
    }

    public function testDoNotParseClass_2()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            ["Other" => new ModelGetter(20, 'JG')]
        ];
        $model->Obj2 = [
            20,
            'Gilberto',
            new ModelPublic(10, 'JG2')
        ];

        $object = new SerializerObject($model);
        $object->setDoNotParse([
            ModelGetter::class
        ]);
        $result = $object->build();

        $this->assertEquals(
            [
                "Obj" => [
                    10,
                    'Joao',
                    ["Other" => new ModelGetter(20, 'JG')]
                ],
                "Obj2" => [
                    20,
                    'Gilberto',
                    ['Id' => '10', 'Name' => 'JG2']
                ]
            ],
            $result
        );
    }

    public function testModelWithFakeProperty()
    {
        $model = new ModelForceProperty();

        $object = new SerializerObject($model);
        $result = $object->build();

        $this->assertEquals(
            [
                "fakeProp" => 20
            ],
            $result
        );
    }
}
