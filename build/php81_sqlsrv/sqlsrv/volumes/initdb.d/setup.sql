if not exists(select * from sys.databases where name = 'exment_database')
    CREATE DATABASE exment_database;
GO