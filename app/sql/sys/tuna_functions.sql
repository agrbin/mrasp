
--
-- tuna_drop_mod_check :: drop mod check from pkey for one table
-- 
create or replace function tuna_drop_mod_check(tb name) returns boolean as $tuna$
  begin
		begin
			execute 'alter table "' || tb || 
					'" drop constraint tuna_check_mod_' || tb || ';';
		exception when others then
			return false;
		end;
		return true;
  end;
$tuna$ language plpgsql;

--
-- tuna_add_mod_check :: add mod check on pkey for one table
-- 
create or replace function tuna_add_mod_check(tb name) returns boolean as $tuna$
	declare
		M integer;
		ID varchar;
		tb_mod integer;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		select mod from system.tables where name = tb into tb_mod;
		perform tuna_drop_mod_check(tb);
		begin
			execute 'alter table "' || tb || 
					'" add constraint tuna_check_mod_' || tb || 
					' check (' || tb||ID || ' % ' || M 
					|| ' = ' || tb_mod || ');';
		exception when others then
			return false;
		end;
		return true;
  end;
$tuna$ language plpgsql;

--
-- tuna_repair_table_ids
-- will update table ids from different TUNA_MAX_TABLE constant to valid TUNA_MAX_TABLES and set
-- table mod to new_mod. this function can also just change mod for some table if called with
-- old_max_tables == tuna_max_tables
--
create or replace function tuna_repair_table_ids(tb name, old_max_tables integer, new_mod integer) returns boolean as $tuna$
	declare
		M integer;
		ID varchar;
		old_mod integer;
		row_cnt integer;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		perform tuna_drop_mod_check(tb);
		execute 'select distinct "'
						|| tb || '".' || tb||ID || ' % ' || old_max_tables 
						|| ' from "' || tb || '";' into old_mod;
		-- make sure that old_max_table parameter told the truth :)
		GET DIAGNOSTICS row_cnt = ROW_COUNT;
		if row_cnt > 1 then
			raise exception E'\n\nprimary keys in table "%" aren\'t build by sequence that increments by provided value %. abort.'
				, tb, old_max_tables;
		end if;
		begin
			execute 'alter table "' || tb || '" drop column _tuna_tmp_id;';
		exception when others then null;
		end;
		execute 'alter table "' || tb || '" add column _tuna_tmp_id bigint;';
		execute 'update "' || tb || '" set _tuna_tmp_id = ' || tb||ID || ';';
		execute 'update "' || tb || '" set _tuna_tmp_id = ' || 
						'((_tuna_tmp_id - ' || old_mod || ') / ' || old_max_tables || ') * '
						|| M || ' + ' || new_mod || ';';
		execute 'update "' || tb || '" set '|| tb||ID || ' = _tuna_tmp_id;';
		execute 'alter table "' || tb || '" drop column _tuna_tmp_id;';
		return true;
	end;

$tuna$ language plpgsql;

--
-- tuna_check_mod
-- checks if provided table is built with her primary key on sequence
-- that rises by TUNA_MAX_TABLE. if not raise exception.
-- if she did, return minimum positive value of that sequence
-- if table is empty, return null.
--
create or replace function tuna_check_mod(tb name) returns integer as $tuna$
	declare
		M integer;
		ID varchar;
		row_cnt integer;
		sol integer;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		execute 'select distinct "'
						|| tb || '".' || tb||ID || ' % ' || M 
						|| ' from "' || tb || '";' into sol;
		GET DIAGNOSTICS row_cnt = ROW_COUNT;
		if row_cnt > 1 then
			raise exception E'primary keys in table "%" aren\'t built by sequence that increments by %.\ndelete problematic rows, change system.config.TUNA_MAX_TABLE value, or empty table "%"\nif you know what sequence built this field in table "%", you can call function tuna_repair_table_ids(tb_name, old_increment_value, new_start_value)\n\n', tb, M, tb, tb;
		end if;
		return sol;
	end;
$tuna$ language plpgsql;

--
-- tuna_check_convention
-- checks if exists tablename with provided name and
-- if she has primary key in format name || ID_SUFIX as
-- it should be by convention. if not, throw an exception
--
create or replace function tuna_check_convention(tb name) returns void as
$tuna$
	declare
		M integer;
		ID varchar;
		pkey name;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		select field_name into pkey
			from system.table_fields
			where tb_name = tb
			order by field_order asc limit 1;
		if not pkey = tb||ID then
			raise exception E'table "%" doesn\'t have "%" on her first field (pkey), as it should by convention.', tb, tb||ID;
		end if;
	end;
$tuna$ language plpgsql;

--
-- __tuna_add_sequence
-- add sequence will add sequence to primary key of table tb with start value mod, and
-- current value first integer greater than the max pkey from that table
-- that gives division remainder with TUNA_MAX_TABLES => mod.
-- this is called from tuna_assign_mod() only. (private function)
--
create or replace function __tuna_add_sequence(tb name, mod integer) returns boolean as $tuna$
	declare
		M integer;
		ID varchar;
		tb_mod integer;
		max_id integer;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		-- delete dependency of primary key to any sequence
		execute 'alter table "'|| tb ||'" alter column '|| tb||ID ||' set default 1;';
		-- delete sequence on this table if exists
    begin
      execute 'drop sequence tuna_seq_' || tb || ';';
    exception when others then null;
    end;
		-- fethc maximum pkey id value
		execute 'select max(' || tb||ID || ') from "' || tb || '";' into max_id;
		-- if null, then zero
		if max_id is null then max_id := mod; end if;
		-- that value must be from sequence with mod 'mod' or something is wrong..
		if not (max_id % M = mod) then
			raise exception 'internalna greska, __tuna_add_sequence krivo pozvan, zovi tunu';
		end if;
		-- increment value by 1 cycle
		max_id := max_id + M;
		-- create sequence on this table
		execute 'create sequence tuna_seq_' || tb ||
						' increment ' || M || ' minvalue ' || mod ||
						' start with ' || max_id || ';';
		-- attach to primary key
		execute 'alter table "' || tb || 
						'" alter column ' || tb||ID || ' set default nextval(' ||
						$$ 'tuna_seq_$$ || tb || $$ '::regclass);$$;
		-- that's it!
		return true;
  end;
$tuna$ language plpgsql;

--
-- tuna_assign_mod
-- assign a mod to a provided table
--
create or replace function tuna_assign_mod(tb name) returns boolean as
$tuna$
	declare
		M integer;
		ID varchar;
		new_mod integer;
		prob_tb name;
  begin
		select id_sufix, tuna_max_tables into strict ID, M
			from system.config;
		-- check if table exists
		perform tb_name from system.table_fields where tb_name = tb;
		if not found then
			raise exception E'table "%" doesn\'t exists in public schema!', tb;
		end if;
		-- check if table has first field in right format (tb.tb_id)
		perform tuna_check_convention(tb);
		-- get the mod from table pkey values
		select tuna_check_mod(tb) into new_mod;
		-- if new_mod is null (or table is empty),
		-- obtain new_mod from tuna_mod sequence while
		-- that value is not existent in system.tables
		while new_mod is null loop
			select nextval('system.tuna_mod'::regclass) into new_mod;
			perform mod from system.tables where mod = new_mod;
			if found then
				new_mod := null;
			end if;
		end loop;
		-- delete entry from system.tables if existed
		delete from system.tables where name = tb;	
		-- try to insert entry into system tables
		begin
			insert into system.tables (name, mod) values(tb, new_mod);
		exception when unique_violation then
			select name into prob_tb from system.tables where mod = new_mod;
			raise exception E'table "%" has the same start value for primary key sequence like table "%". call tuna_repair_table_ids() on either table to fix the problem, or kill one table and call tuna_refresh(). problematic start value: %', prob_tb, tb, new_mod;
		end;
		-- add sequence and everything
		perform __tuna_add_sequence(tb, new_mod);
		-- add check constraint on table primary key
		perform tuna_add_mod_check(tb);
		return true;
	end;
$tuna$ language plpgsql;

--
-- tuna_is_tb_empty :: return if table tb is empty.
--
create or replace function tuna_is_tb_empty(tb name) returns boolean as
$tuna$
	declare
		ID varchar;
		sol integer;
  begin
		select id_sufix into strict ID
			from system.config;
		execute 'select '|| tb||ID ||'  from "'|| tb || '" limit 1';
		GET DIAGNOSTICS sol = ROW_COUNT;
		return 1 - sol;
	end;
$tuna$ language plpgsql;

--
-- tuna_refresh
-- this function checks for potentialy new added tables and
-- update theirs id sequence. also calls create_prep_%tb%
-- this function will delete system.tables table upon execution
--
create or replace function tuna_refresh() returns boolean as
$tuna$
	declare
  begin
		-- kill everything
		delete from system.tables;
		-- frist, build non empty tables
		perform distinct tuna_assign_mod(tb_name)
			from system.table_fields
			where tb_mod is null and not tuna_is_tb_empty(tb_name);
		-- now, build the empty ones
		perform distinct tuna_assign_mod(tb_name)
			from system.table_fields
			where tb_mod is null and tuna_is_tb_empty(tb_name);
		-- update last redesign value
		update system.config set last_redesign = now();
		return true;
  end;
$tuna$
language plpgsql;

--
-- tuna_check
-- check if everything is like it should be, don't change anything.
--
create or replace function tuna_check() returns boolean as
$tuna$
	declare
		prob_tb name;
  begin
		-- is there any entry in system.tables for non existent tables?
		select name into prob_tb from system.tables
			where name not in (select distinct tb_name from system.table_fields);
		if found then
			raise exception E'there a is table "%" in system.tables entries but that table doesn\'t exists in public schema? call tuna_refresh()!', prob_tb;
		end if;
		-- is there any table in public not assigned with mod yet?
		select tb_name into prob_tb from system.table_fields where tb_mod is null;
		if found then
			raise exception 'table "%" has no mod entry in system.tables, call tuna_refresh(). if you just did, you got yourself a problem. call tuna.', prob_tb;
		end if;
		-- are all tables follow naming convention?
		perform tuna_check_convention(name) from system.tables;
		-- are any of tables has wrong mod_value in system.tables?
		select name into prob_tb from system.tables
			where not mod = tuna_check_mod(name)
				and tuna_check_mod(name) is not null;
		if found then
			raise exception 'table "%" has wrong mod entry in system.tables, call tuna_refresh(). if you just did, you got yourself a problem. call tuna.', prob_tb;
		end if;
		return true;
  end;
$tuna$
language plpgsql;
