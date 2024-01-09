<?php

namespace Tests\Serialize;

use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use ByJG\Serializer\SerializerObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelForceProperty;
use Tests\Sample\ModelGetter;
use Tests\Sample\ModelList;
use Tests\Sample\ModelList2;
use Tests\Sample\ModelList3;
use Tests\Sample\ModelPublic;

class SerializerObjectTest extends TestCase
{
    public function testCreateObjectFromModel_ObjectGetter_1elem()
    {
        $model = new ModelGetter(10, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->toArray();

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
            (new PlainTextFormatter())->withBreakLine("</div>")->withStartOfLine("<div>")->process($result)
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
        $result = $object->toArray();

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
            (new PlainTextFormatter())->withBreakLine("</div>")->withStartOfLine("<div>")->process($result)
        );

        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><item0><Id>10</Id><Name>Joao</Name></item0><item1><Id>20</Id><Name>JG</Name></item1></root>\n",
            (new XmlFormatter())->withListElementSuffix()->process($result)
        );

    }

    public function testCreateObjectFromModel_ObjectPublic_1elem()
    {
        $model = new ModelPublic(10, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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

        $result = $object->toArray();

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

        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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
        $result = $object->toArray();

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

        $object = new SerializerObject($modellist->getCollection());
        $result = $object->toArray();

        $this->assertEquals(
            [
                ["Id"=>10, "Name"=>"Joao"],
                ["Id"=>20, "Name"=>"JG"]
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
        $result = $object->toArray();

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
        $result = $object->toArray();

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

        $object->withOnlyString()->toArray();

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

        $result = $object->withOnlyString(false)->withDoNotNullValues()->toArray();

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
        $result = $object->toArray();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->withDoNotNullValues()->toArray();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );

        $model = new ModelPublic(null, null);
        $object = new SerializerObject($model);

        $result = $object->withDoNotNullValues()->toArray();

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testEmptyValues_3()
    {
        $model = new ModelGetter(null, 'Joao');

        $object = new SerializerObject($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->withDoNotNullValues()->toArray();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );
    }

    public function testEmptyValues_4()
    {
        $model = new ModelList();

        $object = new SerializerObject($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'collection' => null
            ],
            $result
        );

        $result = $object->withDoNotNullValues()->toArray();

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
        $result = $object->toArray();

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

        $result = $object->withDoNotNullValues()->toArray();

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
        $object->withStopAtFirstLevel();
        $result = $object->toArray();

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
        $object->withStopAtFirstLevel();
        $result = $object->toArray();

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
        $object->withDoNotParse([
            ModelPublic::class
        ]);
        $result = $object->toArray();

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
        $object2->withDoNotParse([
            ModelPublic::class,
            ModelGetter::class
        ]);
        $result = $object2->toArray();

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
        $object->withDoNotParse([
            ModelGetter::class
        ]);
        $result = $object->toArray();

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
        $result = $object->toArray();

        $this->assertEquals(
            [
                "fakeProp" => 20
            ],
            $result
        );
    }

    public function testSerializeJson()
    {
        $this->assertEquals(["a"=>1, "b"=>2], SerializerObject::instance('{"a": 1, "b": 2}')->fromJson()->toArray());
    }

    public function testSerializeYaml()
    {
        $yaml = file_get_contents(__DIR__ . "/yamlserialize.yml");

        $this->assertEquals(
            [ "name"=> "test",
                "values" => [
                    ["a"=>1, "b"=>2],
                    ["c"=>3, "d"=>4]
                ]
            ],
            SerializerObject::instance($yaml)->fromYaml()->toArray()
        );
    }
}
