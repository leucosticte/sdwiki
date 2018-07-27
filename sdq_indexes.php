CREATE INDEX sdq_wiki_page_rev ON sd_queue (sdq_wiki,sdq_page_id,sdq_rev_id);
CREATE INDEX sdq_rev_id ON sd_queue (sdq_rev_id);
CREATE INDEX sdc_key on sd_cursor (sdc_key);