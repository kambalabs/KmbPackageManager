-- Mcollective actions
DROP TABLE IF EXISTS registrationinventory CASCADE;
CREATE TABLE registrationinventory (
  id   	     			   SERIAL		PRIMARY KEY,
  hostname    			   VARCHAR(256)  	UNIQUE NOT NULL,
  upgradablepackages	    	   TEXT,
  updated_at			   TIMESTAMP		NOT NULL
);
CREATE INDEX registrationinventory_hostname ON registrationinventory (hostname);

DROP TABLE IF EXISTS vulnerability_list CASCADE;
CREATE TABLE vulnerability_list (
  id   	     			   SERIAL		PRIMARY KEY,
  publicid    			   VARCHAR(256)  	UNIQUE NOT NULL,
  package			   VARCHAR(250),
  criticity			   VARCHAR(20)
);
CREATE INDEX vulnerability_list_publicid ON vulnerability_list (publicid);

DROP TABLE IF EXISTS host_vulnerability CASCADE;
CREATE TABLE host_vulnerability (
  id			SERIAL		PRIMARY KEY,
  vulnerability_id    	INTEGER  references vulnerability_list (id),
  host_id		INTEGER references registrationinventory (id)
);
CREATE INDEX host_vulnerability_vulnerability_id ON host_vulnerability (vulnerability_id);
CREATE INDEX host_vulnerability_host_id ON host_vulnerability (host_id);

DROP TABLE IF EXISTS security_logs CASCADE;
DROP TYPE IF EXISTS  status CASCADE;
CREATE TYPE status AS ENUM ('pending', 'success', 'failure', 'cancelled');
CREATE TABLE security_logs (
  id                       SERIAL              PRIMARY KEY,
  username                 VARCHAR(64)         NOT NULL,
  package                  VARCHAR(64)         NOT NULL,
  from_version             VARCHAR(32)         NOT NULL,
  to_version               VARCHAR(32)         NOT NULL,
  server                   VARCHAR(32)         NOT NULL,
  updated_at               TIMESTAMP    NOT NULL,
  status                   status,
  actionid                 VARCHAR(33)  NOT NULL,
  requetid                 VARCHAR(33)  NOT NULL,
);
CREATE INDEX security_logs_username ON security_logs (username);
CREATE INDEX security_logs_package ON security_logs (package);
CREATE INDEX security_logs_server ON security_logs (server);
