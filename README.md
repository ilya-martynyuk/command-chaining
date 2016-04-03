Command chaining bundle
=======

Symfony bundle that implements command chaining functionality.
Other Symfony bundles in the application can register their console commands to be members of a command chain.
When a user runs the main command in a chain, all other commands registered in this chain will be executed as well.
Commands registered as chain members can no longer be executed on their own.