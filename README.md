# PHP Service Layer

This is an application layer that ties different datasources together.

### features

 - caching
 - JSON output
 - database layer (where db specific code lives)
 - processing layer (for processing individual fields)
 - service layer (for performing joins)
 - simple throttling and killswitches
 - modular and layered architecture

### known issues:

1: The caching system is imperfect: The memcache pool is polled every request.

### disclaimer

This project has been used in production, on a set of high traffic game portals.
It has been my first attempt at creating a Microservices toolkit, before the term Microservices was coined.

Since then I have moved away from PHP and embraced Go (Golang) as my language of choice for implementing Microservices.
