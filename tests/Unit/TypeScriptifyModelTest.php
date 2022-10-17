<?php

namespace SalemC\TypeScriptifyLaravelModels\Tests\Unit;

use SalemC\TypeScriptifyLaravelModels\Exceptions\UnsupportedDatabaseConnection;
use SalemC\TypeScriptifyLaravelModels\Tests\Models\ColumnTypeAggregate;
use SalemC\TypeScriptifyLaravelModels\Exceptions\InvalidModelException;
use SalemC\TypeScriptifyLaravelModels\TypeScriptifyModel;
use SalemC\TypeScriptifyLaravelModels\Tests\Models\User;
use SalemC\TypeScriptifyLaravelModels\Tests\TestCase;

use Illuminate\Support\Facades\DB;

use Exception;

class TypeScriptifyModelTest extends TestCase {
    /**
     * Define the environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app) {
        // Setup an unsupported connection type.
        $app['config']->set('database.connections.test', [
            'prefix' => '',
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        parent::getEnvironmentSetUp($app);
    }

    /**
     * Tests that when the supplied model is invalid, an exception is thrown.
     *
     * @return void
     */
    public function test_throws_if_invalid_model_supplied() {
        $this->expectException(InvalidModelException::class);

        new TypeScriptifyModel('\SalemC\TypeScriptifyLaravelModels\Tests\Models\NonExistingModel');
    }

    /**
     * Tests that when the supplied model is invalid, an exception is thrown.
     *
     * @return void
     */
    public function test_throws_if_current_database_connection_is_unsupported() {
        $previousConnection = DB::getDefaultConnection();

        DB::setDefaultConnection('test');

        try {
            new TypeScriptifyModel(User::class);
        } catch (Exception $e) {
            $this->assertEquals(UnsupportedDatabaseConnection::class, get_class($e));
        } finally {
            DB::setDefaultConnection($previousConnection);
        }
    }

    /**
     * Tests that a single dimensional interface is generated from the user model, without hidden attributes.
     *
     * @return void
     */
    public function test_generates_single_dimensional_user_interface_without_hidden_attributes_from_user_model() {
        $result = (new TypeScriptifyModel(User::class))->generate();

        $segments = collect([
            "interface User {",
            "    id: number;",
            "    forename: string;",
            "    surname: string|null;",
            "    email: string;",
            "    role_id: number;",
            "    parent_id: number;",
            "}",
        ]);

        $this->assertEquals($segments->join("\n"), $result);
    }

    /**
     * Tests that a single dimensional interface is generated from the user model, with hidden attributes.
     *
     * @return void
     */
    public function test_generates_single_dimensional_user_interface_with_hidden_attributes_from_user_model() {
        $result = (new TypeScriptifyModel(User::class))
            ->includeHidden(true)
            ->generate();

        $segments = collect([
            "interface User {",
            "    id: number;",
            "    forename: string;",
            "    surname: string|null;",
            "    email: string;",
            "    password: string;",
            "    role_id: number;",
            "    parent_id: number;",
            "}",
        ]);

        $this->assertEquals($segments->join("\n"), $result);
    }

    /**
     * Tests that a multi dimensional interface is generated from the user model, with hidden attributes.
     *
     * @return void
     */
    public function test_generates_multi_dimensional_user_interface_with_hidden_attributes_from_user_model() {
        $result = (new TypeScriptifyModel(User::class, modelCollectorPath: __DIR__ . '/../Models'))
            ->includeHidden(true)
            ->includeRelations(true)
            ->generate();

        $segments = collect([
            "interface Role {",
            "    id: number;",
            "    name: string;",
            "    hidden_column: string|null;",
            "}",
            "",
            "interface User {",
            "    id: number;",
            "    forename: string;",
            "    surname: string|null;",
            "    email: string;",
            "    password: string;",
            "    role: Role;",
            "    parent: User;",
            "}",
        ]);

        $this->assertEquals($segments->join("\n"), $result);
    }

    /**
     * Tests that a multi dimensional interface is generated from the user model, with hidden attributes.
     *
     * @return void
     */
    public function test_generates_multi_dimensional_user_interface_without_hidden_attributes_from_user_model() {
        $result = (new TypeScriptifyModel(User::class, modelCollectorPath: __DIR__ . '/../Models'))
            ->includeRelations(true)
            ->generate();

        $segments = collect([
            "interface Role {",
            "    id: number;",
            "    name: string;",
            "}",
            "",
            "interface User {",
            "    id: number;",
            "    forename: string;",
            "    surname: string|null;",
            "    email: string;",
            "    role: Role;",
            "    parent: User;",
            "}",
        ]);

        $this->assertEquals($segments->join("\n"), $result);
    }

    /**
     * Tests that a multi dimensional interface is generated from the user model, with hidden attributes.
     *
     * @return void
     */
    public function test_generates_single_dimensional_column_type_aggregate_interface_without_hidden_attributes_from_column_type_aggregate_model() {
        $result = (new TypeScriptifyModel(ColumnTypeAggregate::class, modelCollectorPath: __DIR__ . '/../Models'))->generate();

        $segments = collect([
            "interface ColumnTypeAggregate {",
            "    integer: number;",
            "    decimal: number;",
            "    set: string;",
            "    char: string;",
            "    text: string;",
            "    blob: string;",
            "    date: string;",
            "    time: string;",
            "    year: string;",
            "    boolean: boolean;",
            "    float: number;",
            "    bigInteger: number;",
            "    double: number;",
            "    tinyText: string;",
            "    longText: string;",
            "    dateTime: string;",
            "    smallInteger: boolean;",
            "    mediumInteger: number;",
            "    timestamp: string;",
            "    mediumText: string;",
            "    enum: 'a'|'b'|'c';",
            "    castInt: number;",
            "    castReal: number;",
            "    castDate: string;",
            "    castFloat: number;",
            "    castBool: boolean;",
            "    castDouble: number;",
            "    castString: string;",
            "    castInteger: number;",
            "    castDatetime: string;",
            "    castBoolean: boolean;",
            "    castArray: unknown[];",
            "    castEncrypted: string;",
            "    castTimestamp: string;",
            "    castImmutableDate: string;",
            "    castAsStringable: unknown;",
            "    castImmutableDateTime: string;",
            "    castObject: Record<string, unknown>;",
            "    castDecimal: number;",
            "}",
        ]);

        $this->assertEquals($segments->join("\n"), $result);
    }
}
