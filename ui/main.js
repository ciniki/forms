//
// This is the main app for the forms module
//
function ciniki_forms_main() {
    //
    // The panel to list the form
    //
    this.menu = new M.panel('Forms', 'ciniki_forms_main', 'menu', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.forms.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.formtype = 'All';
    this.menu.status = 50;
    this.menu.sections = {
        'statuses':{'label':'Status', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            },
        'types':{'label':'Types', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search form',
            'noData':'No form found',
            },
        'forms':{'label':'Forms', 'type':'simplegrid', 'num_cols':6,
            'headerValues':['Name', 'Status', 'Start', 'End', 'Submissions'],
            'cellClasses':['', '', 'multiline', 'multiline', 'multiline', 'fabuttons'],
            'noData':'No form',
            'addTxt':'Add Form',
            'addFn':'M.ciniki_forms_main.form.open(\'M.ciniki_forms_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.forms.formSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_forms_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_forms_main.menu.panelUID + '_' + s), rsp.forms);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return this.rowFn(s, i, d);
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'types' || s == 'statuses' ) {
            switch(j) {
                case 0: return M.textCount(d.label, d.num_forms);
            }
        }
        if( s == 'forms' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
                case 2: return M.multiline(d.dt_start_date, d.dt_start_time);
                case 3: return M.multiline(d.dt_end_date, d.dt_end_time);
                case 4: return M.multiline(d.num_submissions + ' submitted', d.num_draftsubs + ' in progress');
                case 5: return M.faBtn('&#xf00b;', 'Submissions', 'M.ciniki_forms_main.submissions.open(\'M.ciniki_forms_main.menu.open();\',' + d.id + ');')
                    + M.faBtn('&#xf24d;', 'Duplicate', 'M.ciniki_forms_main.menu.duplicate(' + d.id + ');');
//                case 5: return M.btn('Submissions', 'M.ciniki_forms_main.submissions.open(\'M.ciniki_forms_main.menu.open();\',' + d.id + ');')
//                    + M.btn('Duplicate', 'M.ciniki_forms_main.menu.duplicate(' + d.id + ');');
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'types' ) {
            return 'M.ciniki_forms_main.menu.switchType(\'' + escape(d.label) + '\');';
        }
        if( s == 'statuses' ) {
            return 'M.ciniki_forms_main.menu.switchStatus(\'' + escape(d.status) + '\');';
        }
        if( s == 'forms' ) {
            return 'M.ciniki_forms_main.form.open(\'M.ciniki_forms_main.menu.open();\',\'' + d.id + '\',M.ciniki_forms_main.form.nplist);';
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'types' && d.label == this.formtype ) {
            return 'highlight';
        } 
        if( s == 'statuses' && d.status == this.status ) {
            return 'highlight';
        }
        return '';
    }
    this.menu.switchType = function(t) {
        this.formtype = unescape(t);
        this.open();
    }
    this.menu.switchStatus = function(t) {
        this.status = unescape(t);
        this.open();
    }
    this.menu.duplicate = function(id) {
        M.api.getJSONCb('ciniki.forms.formDup', {'tnid':M.curTenantID, 'form_id':id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            if( rsp.id != null ) {
                M.ciniki_forms_main.menu.status = 10;
                M.ciniki_forms_main.form.open('M.ciniki_forms_main.menu.open();', rsp.id, []);
            } else {
                M.ciniki_forms_main.menu.open();
            }
        });
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.forms.forms', {'tnid':M.curTenantID, 'type':this.formtype, 'status':this.status}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Form
    //
    this.form = new M.panel('Form', 'ciniki_forms_main', 'form', 'mc', 'large mediumaside', 'sectioned', 'ciniki.forms.main.form');
    this.form.data = null;
    this.form.form_id = 0;
    this.form.section_id = 0;
    this.form.selected = 'options';
    this.form.nplist = [];
    this.form.sections = {
        'general':{'label':'Form', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'type':{'label':'Type', 'required':'yes', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{
                '10':'Draft', 
                '50':'Active', 
                '90':'Archived',
                }},
            'flags5':{'label':'Juried', 'type':'flagtoggle', 'default':'off', 'bit':0x10, 'field':'flags',
                'onchange':'M.ciniki_forms_main.form.juryToggle();',
                'on_fields':['flags6'],
                },
            'flags6':{'label':'Voting Open', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags',
                },
            'dt_start':{'label':'Start', 'type':'datetime'},
            'dt_end':{'label':'End', 'type':'datetime'},
            }},
        '_tabs':{'label':'', 'aside':'yes', 'list':{
            'options':{'label':'More Options', 'fn':'M.ciniki_forms_main.form.showSection("options");'},
            'guidelines':{'label':'Guidelines', 'fn':'M.ciniki_forms_main.form.showSection("guidelines");'},
            'termsofuse':{'label':'Terms of Use', 'fn':'M.ciniki_forms_main.form.showSection("termsofuse");'},
            'jurors':{'label':'Jurors', 'visible':'no', 
                'fn':'M.ciniki_forms_main.form.showSection("jurors");',
                },
            }},
        'sections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'editFn':function(s, i, d) {
                return 'M.ciniki_forms_main.form.save("M.ciniki_forms_main.section.open(\'M.ciniki_forms_main.form.open();\',\'' + d.id + '\',M.ciniki_forms_main.form.form_id);");';
                },
            'seqDrop':function(e,from,to) {
                M.api.getJSONCb('ciniki.forms.sectionUpdate', {'tnid':M.curTenantID, 
                    'section_id':M.ciniki_forms_main.form.data.sections[from].id,
                    'sequence':M.ciniki_forms_main.form.data.sections[to].sequence, 
                    'sectionlist':'yes',
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_forms_main.form;
                        p.data.sections = rsp.sections;
                        p.refreshSection("sections");
                    });
                },
            'addTxt':'Add Section',
            'addFn':'M.ciniki_forms_main.form.addSection();',
            },
        'fields':{'label':'Form Fields', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_forms_main.form.selected == 'section' ? 'yes' :'hidden'; },
            'headerValues':['Type', 'Label', 'Req', 'Hide', 'Connection'],
            'seqDrop':function(e,from,to) {
                M.api.getJSONCb('ciniki.forms.fieldUpdate', {'tnid':M.curTenantID, 
                    'field_id':M.ciniki_forms_main.form.data.fields[from].id,
                    'sequence':M.ciniki_forms_main.form.data.fields[to].sequence, 
                    'fieldlist':'yes',
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_forms_main.form;
                        p.data.fields = rsp.fields;
                        p.refreshSection("fields");
                    });
                },
            'addTxt':'Add Field',
            'addFn':'M.ciniki_forms_main.form.addField()',
            },
        'options':{'label':'Other Options', 
            'visible':function() { return M.ciniki_forms_main.form.selected == 'options' ? 'yes' :'hidden'; },
            'fields':{
                'flags1':{'label':'Account Required', 'type':'flagtoggle', 'default':'on', 'bit':0x01, 'field':'flags',
                    },
                'max_submissions':{'label':'Max Submissions', 'type':'text', 'size':'small'},
                'fee_label':{'label':'Fee Label', 'type':'text'},
                'fee_amount':{'label':'Submission Fee', 'type':'text', 'size':'small'},
                'cartsubmit_label':{'label':'Pay Button Label', 'type':'text'},
                'submit_label':{'label':'Submit Label', 'type':'text'},
            }},
        '_thankyou':{'label':'Thank You Message', 
            //'visible':function() { return M.ciniki_forms_main.form.selected == 'thankyou' ? 'yes' :'hidden'; },
            'visible':function() { return M.ciniki_forms_main.form.selected == 'options' ? 'yes' :'hidden'; },
            'fields':{
                'thankyou':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'}
            }},
        '_alreadysubmitted':{'label':'Existing Submission Message', 
            //'visible':function() { return M.ciniki_forms_main.form.selected == 'alreadysubmitted' ? 'yes' :'hidden'; },
            'visible':function() { return M.ciniki_forms_main.form.selected == 'options' ? 'yes' :'hidden'; },
            'fields':{
                'alreadysubmitted':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'}
            }},
        '_loginmsg':{'label':'Login Required Message', 
            //'visible':function() { return M.ciniki_forms_main.form.selected == 'loginmsg' ? 'yes' :'hidden'; },
            'visible':function() { return M.ciniki_forms_main.form.selected == 'options' ? 'yes' :'hidden'; },
            'fields':{
                'loginmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'}
            }},
        '_guidelines':{'label':'Guidelines', 
            'visible':function() { return M.ciniki_forms_main.form.selected == 'guidelines' ? 'yes' :'hidden'; },
            'fields':{
                'guidelines':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'}
            }},
        '_termsofuse':{'label':'Terms of Use', 
            'visible':function() { return M.ciniki_forms_main.form.selected == 'termsofuse' ? 'yes' :'hidden'; },
            'fields':{
                'termsofuse':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'}
            }},
        'jurors':{'label':'Jurors', 'type':'simplegrid', 'num_cols':2, 
            'visible':function() { return M.ciniki_forms_main.form.selected == 'jurors' ? 'yes' :'hidden'; },
            'cellClasses':['', 'buttons'],
            'addTxt':'Add Juror',
            'addFn':'M.ciniki_forms_main.form.save("M.ciniki_forms_main.form.jurorOpen();");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_forms_main.form.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_forms_main.form.form_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_forms_main.form.remove();'},
            }},
        };
    this.form.fieldValue = function(s, i, d) { return this.data[i]; }
    this.form.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.forms.formHistory', 'args':{'tnid':M.curTenantID, 'form_id':this.form_id, 'field':i}};
    }
    this.form.liveSearchCb = function(s, i, value) {
        if( i == 'type' ) {
            var rsp = M.api.getJSONBgCb('ciniki.forms.formFieldSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':value, 'limit':15},
                function(rsp) {
                    M.ciniki_forms_main.form.liveSearchShow(s, i, M.gE(M.ciniki_forms_main.form.panelUID + '_' + i), rsp.results);
                });
        }
    };
    this.form.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'type' && d != null ) { 
            return d.value; 
        }
        return '';
    };
    this.form.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'type' && d != null ) { 
            return 'M.ciniki_forms_main.form.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
        }
    };
    this.form.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.form.juryToggle = function() {
        var v = this.formValue('flags5');
        if( v == 'on' ) {
            this.sections._tabs.list.jurors.visible = 'yes';
            if( this.selected != 'jurors' ) {
                this.showSection('jurors');
            } else {
                this.refreshSection('_tabs');
            }
        }
        else {
            this.sections._tabs.list.jurors.visible = 'no';
            if( this.selected == 'jurors' ) {
                this.showSection('options');
            } else {
                this.refreshSection('_tabs');
            }
        }
    }
    this.form.addSection = function() {
        this.save("M.ciniki_forms_main.section.open('M.ciniki_forms_main.form.open();',0," + this.form_id + ");");
    }
    this.form.showSection = function(s) {
        if( s == 'options' || s == 'guidelines' || s == 'termsofuse' || s == 'jurors' ) {
        //|| s == 'thankyou' || s == 'alreadysubmitted' || s == 'loginmsg' ) {
            this.selected = s;
            this.showHideSections(['options', '_thankyou', '_alreadysubmitted', '_loginmsg', '_guidelines', '_termsofuse', 'fields', 'jurors']);
            this.refreshSection('sections');
            this.refreshSection('_tabs');
        } else {
            this.selected = 'section';
            this.section_id = s;
            this.save("M.ciniki_forms_main.form.open();");
        }
    }
//    this.form.switchTab = function(t) {
//        this.sections._tabs.selected = t;
//        this.showHideSections(['options', '_thankyou', '_alreadysubmitted', '_loginmsg', '_guidelines', '_termsofuse', 'fields', 'jurors']);
//        this.refreshSection('_tabs');
//    }
    this.form.customerOpen = function(cid) {
        M.startApp('ciniki.customers.edit',null,'M.ciniki_forms_main.form.open();','mc',{'next':'M.ciniki_forms_main.form.jurorAdd','customer_id':cid});
    }
    this.form.jurorOpen = function() {
        M.startApp('ciniki.customers.edit',null,'M.ciniki_forms_main.form.open();','mc',{'next':'M.ciniki_forms_main.form.jurorAdd','customer_id':0});
    }
    this.form.jurorAdd = function(cid) {
        // Check if juror exists and ignore, just reload form
        for(var i in this.data.jurors) {
            if( parseInt(this.data.jurors[i].customer_id) == cid ) {
                M.ciniki_forms_main.form.open();
                return false;
            }
        }
        // Add new juror
        M.api.getJSONCb('ciniki.forms.jurorAdd', {'tnid':M.curTenantID, 'form_id':this.form_id, 'customer_id':cid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_forms_main.form.open();
        });
    }
    this.form.jurorDelete = function(id) {
        if( M.confirm('Are you sure you want to remove this juror? All votes they submitted will also be removed. This action cannot be undone.') ) {
            M.api.getJSONCb('ciniki.forms.jurorDelete', {'tnid':M.curTenantID, 'juror_id':id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.form.open();
            });
        }
    }
    this.form.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            if( (d.flags&0x01) ) {
                return d.label + ' ' + M.subdue('(repeat ', d.min_repeats + ' - ' + d.max_repeats, ')');
            }
            return d.label;
        }
        if( s == 'jurors' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return M.btn('Remove', 'M.ciniki_forms_main.form.save(\'M.ciniki_forms_main.form.jurorDelete(' + d.id + ');\');');
            }
        }
        if( s == 'fields' ) {
            switch(j) {
                case 0: return d.type_text;
                case 1: return d.label;
                case 2: return (d.flags&0x01) == 0x01 ? '*' : '';
                case 3: return (d.flags&0x02) == 0x02 ? 'Y' : '';
                case 4: return d.field_ref_text;
            }
        }
    }
    this.form.listClass = function(s, i, d) {
        if( (s == 'sections' || s == '_tabs') && i == this.selected ) {
            return 'highlight';
        }
        return '';
    }
    this.form.rowClass = function(s, i, d) {
        if( this.selected == 'section' && d.id == this.section_id ) {
            this.sections.fields.label = d.label;
            return 'highlight';
        }
        return '';
    }
    this.form.rowFn = function(s, i, d) {
        if( s == 'sections' ) {
            return 'M.ciniki_forms_main.form.save("M.ciniki_forms_main.form.showSection(' + d.id + ');");';
        }
        if( s == 'jurors' ) {
            return 'M.ciniki_forms_main.form.save("M.ciniki_forms_main.form.customerOpen(' + d.customer_id + ');");';
        }
        if( s == 'fields' ) {
            return 'M.ciniki_forms_main.field.open(\'M.ciniki_forms_main.form.open();\',' + d.id + ',' + d.section_id + ',' + this.form_id + ',M.ciniki_forms_main.form.data.field_ids);';
        }
    }
    this.form.addField = function() {
        this.save('M.ciniki_forms_main.field.open(\'M.ciniki_forms_main.form.open();\',0,' + this.section_id + ',' + this.form_id + ',[]);');
    }
    this.form.open = function(cb, fid, list) {
        if( fid != null ) { 
            this.form_id = fid; 
            this.selected = 'options'; 
            this.section_id = 0;
        }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.forms.formGet', {'tnid':M.curTenantID, 'form_id':this.form_id, 'section_id':this.section_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.form;
            p.data = rsp.form;
            p.sections.general.fields.flags6.visible = (rsp.form.flags&0x10) == 0x10 ? 'yes' : 'no';
            p.sections._tabs.list.jurors.visible = (rsp.form.flags&0x10) == 0x10 ? 'yes' : 'no';
            p.refresh();
            p.show(cb);
        });
    }
    this.form.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_forms_main.form.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.form_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.forms.formUpdate', {'tnid':M.curTenantID, 'form_id':this.form_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.forms.formAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.form.form_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.form.remove = function() {
        if( M.confirm('Are you sure you want to remove form?') ) {
            M.api.getJSONCb('ciniki.forms.formDelete', {'tnid':M.curTenantID, 'form_id':this.form_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.form.close();
            });
        }
    }
    this.form.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.form_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_forms_main.form.save(\'M.ciniki_forms_main.form.open(null,' + this.nplist[this.nplist.indexOf('' + this.form_id) + 1] + ');\');';
        }
        return null;
    }
    this.form.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.form_id) > 0 ) {
            return 'M.ciniki_forms_main.form.save(\'M.ciniki_forms_main.form.open(null,' + this.nplist[this.nplist.indexOf('' + this.form_id) - 1] + ');\');';
        }
        return null;
    }
    this.form.addButton('save', 'Save', 'M.ciniki_forms_main.form.save();');
    this.form.addClose('Cancel');
    this.form.addButton('next', 'Next');
    this.form.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Section
    //
    this.section = new M.panel('Section', 'ciniki_forms_main', 'section', 'mc', 'medium', 'sectioned', 'ciniki.forms.main.section');
    this.section.data = null;
    this.section.form_id = 0;
    this.section.section_id = 0;
    this.section.nplist = [];
    this.section.sections = {
        'general':{'label':'', 'fields':{
            'label':{'label':'Label', 'type':'text', 'required':'yes'},
            'sequence':{'label':'Order', 'type':'text'},
//            'flags':{'label':'Options', 'type':'text'},
            'flags1':{'label':'Repeatable', 'type':'flagtoggle', 'default':'no', 'field':'flags', 'bit':0x01,
                'on_fields':['repeat_prefix', 'min_repeats', 'max_repeats'],
                },
            'repeat_prefix':{'label':'Repeat Label', 'visible':'no', 'type':'text'},
            'min_repeats':{'label':'Min Repeats', 'visible':'no', 'type':'number', 'size':'small'},
            'max_repeats':{'label':'Max Repeats', 'visible':'no', 'type':'number', 'size':'small'},
            }},
        '_description':{'label':'Description', 'aside':'yes', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_forms_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_forms_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_forms_main.section.remove();'},
            }},
        };
    this.section.fieldValue = function(s, i, d) { return this.data[i]; }
    this.section.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.forms.sectionHistory', 'args':{'tnid':M.curTenantID, 'section_id':this.section_id, 'field':i}};
    }
    this.section.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.form_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.forms.sectionGet', {'tnid':M.curTenantID, 'section_id':this.section_id, 'form_id':this.form_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.section;
            p.data = rsp.section;
            p.form_id = rsp.section.form_id;
            if( (rsp.section.flags&0x01) == 0x01 ) {
                p.sections.general.fields.repeat_prefix.visible = 'yes';
                p.sections.general.fields.min_repeats.visible = 'yes';
                p.sections.general.fields.max_repeats.visible = 'yes';
            } else {
                p.sections.general.fields.repeat_prefix.visible = 'no';
                p.sections.general.fields.min_repeats.visible = 'no';
                p.sections.general.fields.max_repeats.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.section.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_forms_main.section.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.section_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.forms.sectionUpdate', {'tnid':M.curTenantID, 'section_id':this.section_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.forms.sectionAdd', {'tnid':M.curTenantID, 'form_id':this.form_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.section.section_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.section.remove = function() {
        if( M.confirm('Are you sure you want to remove section?') ) {
            M.api.getJSONCb('ciniki.forms.sectionDelete', {'tnid':M.curTenantID, 'section_id':this.section_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.section.close();
            });
        }
    }
    this.section.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_forms_main.section.save(\'M.ciniki_forms_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) + 1] + ');\');';
        }
        return null;
    }
    this.section.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) > 0 ) {
            return 'M.ciniki_forms_main.section.save(\'M.ciniki_forms_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) - 1] + ');\');';
        }
        return null;
    }
    this.section.addButton('save', 'Save', 'M.ciniki_forms_main.section.save();');
    this.section.addClose('Cancel');
    this.section.addButton('next', 'Next');
    this.section.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Form Field
    //
    this.field = new M.panel('Form Field', 'ciniki_forms_main', 'field', 'mc', 'medium', 'sectioned', 'ciniki.forms.main.field');
    this.field.data = null;
    this.field.form_id = 0;
    this.field.section_id = 0;
    this.field.field_id = 0;
    this.field.refs = [];
    this.field.nplist = [];
    this.field.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
//            'section_id':{'label':'Section', 'type':'select', 'complex_options':{'value':'id', 'name':'label'}, 'options':{}},
            'section_id':{'label':'Section', 'type':'select', 'idnames':'yes', 'options':{}},
            'ftype':{'label':'Type', 'type':'select', 
                'options':{
                    'date':'Date',
                    'number':'Number',
                    'price':'Price',
                    'phone':'Phone Number',
                    'email':'Email Address',
                    'address':'Address',
                    'url':'Website',
                    'text':'Text',
                    'textarea':'Multiline',
                    'select':'Dropdown',
                    'radio':'Radio List',
                    'checkbox':'Checkbox',
                    'content':'Information',
                    'image':'Image',
//                    'document':'Document',
                    'break':'Break Between Fields',
                    },
                'onchange':'M.ciniki_forms_main.field.setupOptions',
                },
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'label':{'label':'Label', 'type':'text'},
            'flags1':{'label':'Required', 'type':'flagtoggle', 'field':'flags', 'bit':0x01, 'default':'no'},
            'flags2':{'label':'Hide from Jurors', 'type':'flagtoggle', 'field':'flags', 'bit':0x02, 'default':'no'},
            'field_ref':{'label':'Connect To', 'type':'select', 'options':{}},
            }},
        '_options':{'label':'Options', 'visible':'hidden', 'fields':{
            'max-characters':{'label':'Maximum Characters', 'type':'text', 'size':'small', 'active':'no'},
            'max-words':{'label':'Word Limit', 'type':'text', 'size':'small', 'active':'no'},
            'size':{'label':'Size', 'type':'toggle', 'toggles':{'tiny':'Tiny', 'small':'Small', 'medium':'Medium', 'large':'Large', 'xlarge':'X-Large'}, 'active':'no'},
            'option-1':{'label':'Option 1', 'type':'text', 'active':'no'},
            'option-2':{'label':'Option 2', 'type':'text', 'active':'no'},
            'option-3':{'label':'Option 3', 'type':'text', 'active':'no'},
            'option-4':{'label':'Option 4', 'type':'text', 'active':'no'},
            'option-5':{'label':'Option 5', 'type':'text', 'active':'no'},
            'option-6':{'label':'Option 6', 'type':'text', 'active':'no'},
            'option-7':{'label':'Option 7', 'type':'text', 'active':'no'},
            'option-8':{'label':'Option 8', 'type':'text', 'active':'no'},
            'option-9':{'label':'Option 9', 'type':'text', 'active':'no'},
            'option-10':{'label':'Option 10', 'type':'text', 'active':'no'},
            'option-11':{'label':'Option 11', 'type':'text', 'active':'no'},
            'option-12':{'label':'Option 12', 'type':'text', 'active':'no'},
            'option-13':{'label':'Option 13', 'type':'text', 'active':'no'},
            'option-14':{'label':'Option 14', 'type':'text', 'active':'no'},
            'option-15':{'label':'Option 15', 'type':'text', 'active':'no'},

            'min-width':{'label':'Minimum Image Width (pixels)', 'type':'text', 'size':'small', 'active':'no'},
            'min-height':{'label':'Minimum Image Height (pixels)', 'type':'text', 'size':'small', 'active':'no'},
            'max-width':{'label':'Maximum Image Width (pixels)', 'type':'text', 'size':'small', 'active':'no'},
            'max-height':{'label':'Maximum Image Height (pixels)', 'type':'text', 'size':'small', 'active':'no'},
            }},
        '_description':{'label':'Description', 'aside':'yes', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_forms_main.field.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_forms_main.field.field_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_forms_main.field.remove();'},
            }},
        };
    this.field.fieldValue = function(s, i, d) { return this.data[i]; }
    this.field.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.forms.fieldHistory', 'args':{'tnid':M.curTenantID, 'field_id':this.field_id, 'field':i}};
    }
    this.field.setupOptions = function() {
        var t = this.formFieldValue('general', 'ftype');
        // Turn options off and hide all options by default
        this.sections._options.visible = 'hidden';
        for(var i in this.sections._options.fields) {
            this.sections._options.fields[i].active = 'no';
        }
        // Setup the _options section
        if( t == 'text' ) {
            this.sections._options.visible = 'yes';
            this.sections._options.fields['max-characters'].active = 'yes';
        } else if( t == 'textarea' ) {
            this.sections._options.visible = 'yes';
            this.sections._options.fields['max-words'].active = 'yes';
            this.sections._options.fields['size'].active = 'yes';
        } else if( t == 'select' || t == 'radio' ) {
            this.sections._options.visible = 'yes';
            this.sections._options.fields['option-1'].active = 'yes';
            this.sections._options.fields['option-2'].active = 'yes';
            this.sections._options.fields['option-3'].active = 'yes';
            this.sections._options.fields['option-4'].active = 'yes';
            this.sections._options.fields['option-5'].active = 'yes';
            this.sections._options.fields['option-6'].active = 'yes';
            this.sections._options.fields['option-7'].active = 'yes';
            this.sections._options.fields['option-8'].active = 'yes';
            this.sections._options.fields['option-9'].active = 'yes';
            this.sections._options.fields['option-10'].active = 'yes';
            this.sections._options.fields['option-11'].active = 'yes';
            this.sections._options.fields['option-12'].active = 'yes';
            this.sections._options.fields['option-13'].active = 'yes';
            this.sections._options.fields['option-14'].active = 'yes';
            this.sections._options.fields['option-15'].active = 'yes';
        } else if( t == 'image' ) {
            this.sections._options.visible = 'yes';
            this.sections._options.fields['min-width'].active = 'yes';
            this.sections._options.fields['min-height'].active = 'yes';
            this.sections._options.fields['max-width'].active = 'yes';
            this.sections._options.fields['max-height'].active = 'yes';
        }
        this.showHideSection('_options');
        this.refreshSection('_options');

        // Setup the field refs options
        this.sections.general.fields.field_ref.options = {'':'No Reference'};
        for(var i in this.refs) {
            if( this.refs[i].type == t ) {
                this.sections.general.fields.field_ref.options[i] = this.refs[i].module + ' - ' + this.refs[i].name;
            }
        }
        this.refreshFormField('general', 'field_ref');
    }
    this.field.open = function(cb, fid, sid, form_id, list) {
        if( fid != null ) { this.field_id = fid; }
        if( sid != null ) { this.section_id = sid; }
        if( form_id != null ) { this.form_id = form_id; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.forms.fieldGet', {'tnid':M.curTenantID, 'field_id':this.field_id, 'form_id':this.form_id, 'section_id':this.section_id, 'sectionlist':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.field;
            p.data = rsp.field;
            p.refs = rsp.refs;
            p.sections.general.fields.section_id.options = rsp.sections;
            p.refresh();
            p.show(cb);
            p.setupOptions();
        });
    }
    this.field.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_forms_main.field.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.field_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.forms.fieldUpdate', {'tnid':M.curTenantID, 'field_id':this.field_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.forms.fieldAdd', {'tnid':M.curTenantID, 'section_id':this.section_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.field.field_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.field.remove = function() {
        if( M.confirm('Are you sure you want to remove field?') ) {
            M.api.getJSONCb('ciniki.forms.fieldDelete', {'tnid':M.curTenantID, 'field_id':this.field_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.field.close();
            });
        }
    }
    this.field.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_forms_main.field.save(\'M.ciniki_forms_main.field.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) + 1] + ');\');';
        }
        return null;
    }
    this.field.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) > 0 ) {
            return 'M.ciniki_forms_main.field.save(\'M.ciniki_forms_main.field.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) - 1] + ');\');';
        }
        return null;
    }
    this.field.addButton('save', 'Save', 'M.ciniki_forms_main.field.save();');
    this.field.addClose('Cancel');
    this.field.addButton('next', 'Next');
    this.field.addLeftButton('prev', 'Prev');

    
    //
    // The submissions panel for a form
    //
    //
    // The panel to edit Form
    //
    this.submissions = new M.panel('Submissions', 'ciniki_forms_main', 'submissions', 'mc', 'large mediumaside', 'sectioned', 'ciniki.forms.main.submissions');
    this.submissions.data = null;
    this.submissions.form_id = 0;
    this.submissions.status = 90;
    this.submissions.object = '';
    this.submissions.object_id = '';
    this.submissions.nplist = [];
    this.submissions.sections = {
        'form_details':{'label':'Form', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        'statuses':{'label':'Submission Status', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            },
        'objects':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1, 'visible':'no',
            },
        'submissions':{'label':'Submissions', 'type':'simplegrid', 'num_cols':5,
            'headerValues':[],
            'cellClasses':[],
            'dataMaps':[],
            'noData':'No Submissions',
            },
        }
    this.submissions.cellValue = function(s, i, j, d) {
        if( s == 'form_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'statuses' ) {
            return M.textCount(d.label, d.num_submissions);
        }
        if( s == 'objects' ) {
            return M.textCount(d.label, d.num_submissions);
        }
        if( s == 'submissions' ) {
            switch(this.sections.submissions.dataMaps[j]) {
                case 'name': return d.display_name;
                case 'status': return d.status_text;
                case 'object': return d.object_text;
                case 'submitted': return M.multiline(d.dt_last_submitted_date, d.dt_last_submitted_time);
                case 'ranking': return M.multiline(d.rank, d.num_votes + ' of ' + this.data.form.num_jurors);
            }
        }
    }
    this.submissions.rowClass = function(s, i, d) {
        if( s == 'statuses' && d.id == this.status ) {
            return 'highlight';
        }
        if( s == 'objects' && d.object == this.object && d.object_id == this.object_id ) {
            return 'highlight';
        }
        return '';
    }
    this.submissions.rowFn = function(s, i, d) {
        if( s == 'statuses' ) {
            return 'M.ciniki_forms_main.submissions.switchStatus(' + d.id + ');';
        }
        if( s == 'objects' ) {
            return 'M.ciniki_forms_main.submissions.switchObject(\'' + d.object + '\',\'' + d.object_id + '\');';
        }
        if( s == 'submissions' ) {
            return 'M.ciniki_forms_main.submission.open(\'M.ciniki_forms_main.submissions.open();\',\'' + d.id + '\',M.ciniki_forms_main.submissions.data.nplist);';
        }
        return '';
    }
    this.submissions.switchStatus = function(id) {
        this.status = id;
        this.open();
    }
    this.submissions.switchObject = function(o,id) {
        this.object = o;
        this.object_id = id;
        this.open();
    }
    this.submissions.open = function(cb, fid, list) {
        if( fid != null ) { 
            this.form_id = fid; 
            this.status = 90; 
            this.object = '';
            this.object_id = '';
        }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.forms.submissions', {'tnid':M.curTenantID, 'form_id':this.form_id, 'status':this.status, 'object':this.object, 'object_id':this.object_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.submissions;
            p.data = rsp;
            p.sections.submissions.num_cols = 2;
            p.sections.submissions.headerValues = ['Name'];
            p.sections.submissions.cellClasses = [''];
            p.sections.submissions.dataMaps = ['name'];
            if( p.status == 0 ) {
                p.sections.submissions.num_cols++;
                p.sections.submissions.headerValues.push('Status');
                p.sections.submissions.cellClasses.push('');
                p.sections.submissions.dataMaps.push('status');
            }
/*            if( p.object == '' ) {
                p.sections.submissions.num_cols++;
                p.sections.submissions.headerValues.push('From');
                p.sections.submissions.cellClasses.push('');
                p.sections.submissions.dataMaps.push('object');
            } */
            p.sections.submissions.headerValues.push('Submitted');
            p.sections.submissions.cellClasses.push('multiline aligncenter');
            p.sections.submissions.dataMaps.push('submitted');
            // Check if juried form
            if( (p.data.form.flags&0x10) == 0x10 ) {
                p.sections.submissions.num_cols += 1;
                p.sections.submissions.headerValues.push('Ranking');
                p.sections.submissions.cellClasses.push('multiline aligncenter');
                p.sections.submissions.dataMaps.push('ranking');
            }
            p.sections.submissions.headerClasses = p.sections.submissions.cellClasses;
            p.refresh();
            p.show(cb);
        });
    }
    this.submissions.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.form_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_forms_main.submissions.open(null,' + this.nplist[this.nplist.indexOf('' + this.form_id) + 1] + ');';
        }
        return null;
    }
    this.submissions.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.form_id) > 0 ) {
            return 'M.ciniki_forms_main.submissions.open(null,' + this.nplist[this.nplist.indexOf('' + this.form_id) - 1] + ');';
        }
        return null;
    }
    this.submissions.addClose('Back');
    this.submissions.addButton('next', 'Next');
    this.submissions.addLeftButton('prev', 'Prev');

    //
    // The submission panel to display the details of a submission
    //
    this.submission = new M.panel('Submission', 'ciniki_forms_main', 'submission', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.forms.main.submission');
    this.submission.data = null;
    this.submission.submission_id = 0;
    this.submission.nplist = [];
    this.submission.sections = {};
//    this.submission.fieldHistoryArgs = function(s, i) {
//        return {'method':'ciniki.forms.submissionHistory', 'args':{'tnid':M.curTenantID, 'submission_id':this.submission_id, 'field':i}};
//    }
    this.submission.cellValue = function(s, i, j, d) {
        if( s == 'submission_details' || s == 'customer_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        } 
        else if( j == 0 ) {
            return d.label;
        }
        else if( j == 1 ) {
            if( d.ftype != null && d.ftype == 'address' ) {
                return M.formatAddress(d.value);
            }
            else if( d.ftype != null && d.ftype == 'checkbox' ) {
                if( d.value != null && d.value == 'on' ) {
                    return 'Yes';
                }
                return '';
            }
            else if( d.ftype != null && d.ftype == 'textarea' ) {
                return M.formatHtml(d.value != null ? d.value : '');
            }
            else if( d.ftype != null && d.ftype == 'image' ) {
                if( d.value != null && d.value > 0 ) {
                    return '<div class="image_preview"><img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':d.value, 'version':'original', 'maxheight':600}) + '\'/></div>';
                }
            } 
            else {
                return d.value;
            }
        }
    }
    this.submission.open = function(cb, sid, list) {
        if( sid != null ) { this.submission_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.forms.submissionGet', {'tnid':M.curTenantID, 'submission_id':this.submission_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_forms_main.submission;
            p.data = rsp.form;
            p.sections = {
                'submission_details':{'label':'Submission', 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
                    'cellClasses':['label', ''],
                    },
                'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
                    'cellClasses':['label', ''],
                    'visible':(rsp.form.customer_details != null ? 'yes' : 'no'),
                    },
                };
            for(var i in rsp.form.sections) {
                var subsec = 1;
                var repeats = 1;
                if( (rsp.form.sections[i].flags&0x01) == 0x01 ) { // Repeatable
                    repeats = rsp.form.sections[i].max_repeats;
                }
                var sid = '';

                for(var repeat = 1; repeat <= repeats; repeat++) {
                    sid = 's_' + rsp.form.sections[i].id + '_' + subsec;
                    if( repeats > 1 ) {
                        sid += '_' + repeat;
                    }
                    var label = rsp.form.sections[i].label;
                    if( repeats > 1 && rsp.form.sections[i].repeat_prefix != '' ) {
                        label = rsp.form.sections[i].repeat_prefix + ' ' + repeat;
                    }
                    p.sections[sid] = {'label':label, 'type':'simplegrid', 'num_cols':2,
                        'cellClasses':['label', ''],
                        };
                    p.data[sid] = [];

                    for(var j in rsp.form.sections[i].fields) {
                        if( rsp.form.sections[i].fields[j].ftype == 'break' ) {
                            subsec++;
                            sid = 's_' + rsp.form.sections[i].id + '_' + subsec;
                            if( (rsp.form.sections[i].flags&0x01) == 0x01 ) { // Repeatable
                                sid += '_' + repeat;
                            }
                            p.sections[sid] = {'label':rsp.form.sections[i].fields[j].label, 'type':'simplegrid', 'num_cols':2,
                                'cellClasses':['label', ''],
                                };
                            p.data[sid] = [];
                            continue;
                        }
                        if( repeats > 1 ) {
                            var field = {
                                'label':rsp.form.sections[i].fields[j].label,
                                'ftype':rsp.form.sections[i].fields[j].ftype,
                                'value':'',
                                };
                            if( rsp.form.sections[i].fields[j].values != null 
                                && rsp.form.sections[i].fields[j].values[repeat] != null 
                                ) {
                                field.value = rsp.form.sections[i].fields[j].values[repeat];
                            }
                            p.data[sid].push(field);
                        } else {
                            p.data[sid].push(rsp.form.sections[i].fields[j]);
                        }
                    }
                }
            }
            p.sections['_buttons'] = {'label':'', 'buttons':{
                'delete':{'label':'Delete', 'fn':'M.ciniki_forms_main.submission.remove();'},
                }};
            p.refresh();
            p.show(cb);
        });
    }
    this.submission.remove = function() {
        M.confirm('Are you sure you want to remove this submission?', 'Delete Submission', function(rsp) {
            M.api.getJSONCb('ciniki.forms.submissionDelete', {'tnid':M.curTenantID, 'submission_id':M.ciniki_forms_main.submission.submission_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_forms_main.submission.close();
            })
        });
    }
    this.submission.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.submission_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_forms_main.submission.open(null,' + this.nplist[this.nplist.indexOf('' + this.submission_id) + 1] + ');';
        }
        return null;
    }
    this.submission.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.submission_id) > 0 ) {
            return 'M.ciniki_forms_main.submission.open(null,' + this.nplist[this.nplist.indexOf('' + this.submission_id) - 1] + ');';
        }
        return null;
    }
//    this.submission.addButton('save', 'Save', 'M.ciniki_forms_main.submission.save();');
    this.submission.addClose('Close');
    this.submission.addButton('next', 'Next');
    this.submission.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_forms_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
