create database test
character utf
collate utf8_general_ci;

create table users(
    id serial,
    email varchar(60),
    primary key (id)
);

create table posts(
    id serial,
    title varchar(250),
    body text,
    user_id int not null,
    primary key (id)
    foreign key (user_id) references users(id) on update cascade on delete restrict;
);

create table comments(
    id serial,
    name varchar(250),
    body text,
    post_id int not null,
    user_id int not null,
    primary key (id)
    foreign key (post_id) references posts(id) on update cascade on delete restrict
    foreign key (user_id) references users(id) on update cascade on delete restrict;
);