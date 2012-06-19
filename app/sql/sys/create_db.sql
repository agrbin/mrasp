-- TODO tu smo satli
-- potrebno je napraviti raspored bazu na p4.tel.fer.hr
-- nakon toga transofmirati je da je trenutna verzija
-- tune zna citati.

-- zamisljeno je da ovaj fajl pozivas kroz psql

create database "rasp" encoding 'UTF8';
create role "rasp" login password 'rasp';
grant all privileges on database "rasp" to "rasp";
alter database "rasp" owner to "rasp";


