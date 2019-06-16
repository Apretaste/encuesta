ALTER TABLE _survey_answer_choosen ADD COLUMN person_id integer
    REFERENCES person(id) on delete cascade on update cascade;

UPDATE _survey_answer_choosen set person_id = (select id from person where person.email = _survey_answer_choosen.email);