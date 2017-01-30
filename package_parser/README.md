Package Parser
===========

Python scripts to:
1. Get all package names from Packagist using [Packagist API](https://packagist.org/apidoc) by sending a GET request to `https://packagist.org/packages/list.json`
2. Extract each packages' github repository url using [Packagist API](https://packagist.org/apidoc) again by sending a GET request to `https://packagist.org/packages/PACKAGE_NAME.json`
3. Extract Github repository name from the GitHub repository URL.
4. Parse each packages' list of contributors on github using [GitHub API](https://developer.github.com/v3/) by sending a GET request to `https://api.github.com/repos/REPO_NAME/contributors`.

Requirements
------------

  * Python 2.7 or higher.
  * [pip](https://pypi.python.org/pypi/pip).
  * [RabbitMQ](https://www.rabbitmq.com/).
  * Python packages listed in `requirements.txt`.

Usage
------------

1. Run `$ pip install -r requirements.txt` in your terminal to install all tge dependencies.
2. Update your RabbitMQ configuration in `config.py`.
3. Run `$ python package_parser_task.py` in your terminal to queue all the packages in the parser queue.
4. Now you can start your `parser queue` worker(workers). These workers will parse all the package information and will add all the parsed info to `persistor queue`.
These workers use GitHub API to get the list of contributors. GitHub has a limit on their API usage and the liit for anonyous callers are very low. You can use your GitHub account username and [API token](https://github.com/blog/1509-personal-api-tokens) to lift the limit.
To start one queue worker just run `$ python package_parser_queue_worker.py -u YOUR_GITHUB_USERNAME -t YOUR_GITHUB_API_TOKEN &` in your terminal.
You can run the same command multiple time to increase the number of workers. I recommend 5 workers running in parallel.
5. Now you need to start your `persistor queue` worker (only one!). This worker just gets the packages and all their info one by one and will send a PHP command to our symfony application. The symfony application is responsible for saving these data inti the database. To start the `persistor queue` worker just run `$ python package_persistor_queue_worker.py &` in your terminal. You can start this worker in parallel with the other `parser queue` workers.

As mentioned above, the symfony application is responsible for saving all the data into the database so make sure your [symfony application is up and running](https://github.com/aliminaei/pathfinder) before starting the `persistor queue` worker.



