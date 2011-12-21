# Txerpa

A basic library to represent the Txerpa REST API as a PHP class (see [https://www.txerpa.com/info/api](https://www.txerpa.com/info/api) for more
information about the Txerpa API).

This library presents all the Txerpa API operations as a PHP class, it uses exceptions to handle errors and it's written
to handle errors in a consistent manner.

The Txerpa API has some particularities I don't like, for example, if you are performing a search, when the criteria
matches some elements they will be presented as an array, but if there is no matches it will return a 404 instead of an
empty collection, this library handles this exceptions to provide a consistent expirience, so instead of throwing an
exception it will return an empty array when a collection is expected or null when a single object was expected.


## Installation

Click the `download` link above or `git clone git://github.com/habitissimo/txerpa-api.git`

From the cloned directory do:

    git submodule init
    git submodule update

This will load the [curl](https://github.com/hugochinchilla/curl) wrapper dependency on which this one relies.

## Usage

### Initialization

Simply require and initialize the `Txerpa` class like so:

	require_once 'txerpa.php';
	$txerpa = new Txerpa();
    
You will need to set your authentication credentials to the previous example to work.
Open `lib/txerpa.php` and in the `__construct()` method adjust the following values:

    $this->username = 'your_username';
    $this->password = 'secret';


### Performing a Request

	$response = $txerpa->currencies();


## Testing

Uses [ztest](http://github.com/jaz303/ztest), simply download it to `path/to/txerpa/test/ztest` (or anywhere else in your php include_path)

Then run `test/runner.php`

## Contact

Problems, comments, and suggestions all welcome: [hchinchilla@habitissimo.com](mailto:hchinchilla@habitissimo.com)