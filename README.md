This Vanilla Forums plugin is targetted to developers who need to maintain entries in a database table from the dashboard.
This could be useful if you have more complex settings that you must store in a database table. By the help of this plugin, maintaining such config settings from the dashboard becomes a breeze.

Here is a usage example. Lets assume you have a table "Example" with the following fields
- ExampleID (primary key)
- Name
- Description
- Configuration (comma separated list of let's say up to 20 strings)

You would have to start by adding the following line to your plugins info array: `'RequiredPlugins' => ['dashboardCRUD' => '>= 0.1.0'],`. Although this isn't needed for your plugin to work, it would prevent users of your plugin to set this dependency.

Now if you want to edit your configurations, you would need an "API endpoint" for the crud helper plugin. We choose "/settings/example/config".
Now we create a routing for the CRUD actions below.
CREATE: "/settings/example/config/add"
READ: "/settings/example/config"
UPDATE: "/settings/example/config/edit/ID"
DELETE: "/settings/example/config/delete/ID"
~~~
public function settingsController_example_create($sender, $args) {
    if (isset($args[0]) && strtolower($args[0]) == 'config') {
        $method = strtolower(val(1, $args, 'index'));
        switch ($method) {
            case 'add':
                break;
            case 'edit':
                break;
            case 'delete':
                break;
            case 'index':
            default:
        }
    }
}
~~~

That is the routing base structure. Filled with this plugins helper calls, it would looks like that:
~~~
public function settingsController_example_create($sender, $args) {
    // Show dashboard panel.
    $sender->addSideMenu('/settings/example/configuration');
    if (isset($args[0]) && strtolower($args[0]) == 'config') {
        // Create an instance of the helper class.
        $crud = new DashboardCRUDPlugin(
            'Example', // The name of the table is required
            '/settings/example/config' // The link to our index page is required
            // 'ExampleID' // Not needed, since PrimaryKey in this example is named "TableNameID"
        );
        switch (strtolower(val(1, $args, 'index'))) {
            case 'add':
                $crud->add(
                    $sender,
                    [
                        'Title' => t('Add an example row'), // optional
                        'Description' => t('Bla, bla, bla'), // optional
                    ]
                );
                break;
            case 'edit':
                $crud->edit(
                    $sender,
                    ['PrimaryKeyValue' => val(2, $args, 0)] // Required.
                );
                break;
            case 'delete':
                $crud->delete(
                    $sender,
                    ['PrimaryKeyValue' => val(2, $args, 0)] // Required.
                );
                break;
            case 'index':
            default:
                // The index view should be default. That way even
                // /settings/example/config/brokenlink would show our index page.
                $crud->index(
                    $sender,
                    [
                        'Title' => t('List of example data'),
                        'Description' => t('The Configuration column is hidden here. At least this is only an overview and there is no reason to show all columns.<br />And the primary key is also hidden here.'),
                        'Blacklist' => ['ExampleID', 'TestData']
                    ]
                );
        }
    }
}
~~~

Use this code as a blueprint for how the dashboardCRUD plugin can be used in your plugin.