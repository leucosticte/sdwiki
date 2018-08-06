import pywikibot
import pprint
import sys
from pywikibot import pagegenerators

class TestScript:
    def __init__(self, test, wikipedia):
        self.siteTest = test
        self.siteWikipedia = wikipedia
        #self.bigLimit = 500
        #self.smallLimit = 50
        self.bigLimit = 500
        self.smallLimit = 50
        self.revIdsAlreadyGathered = []
    
    def getCategoryMembers( self, category ):
        myParameters = {'action': 'query',
            'generator': 'categorymembers',
            #'gcmtitle': category,
            'gcmtitle': 'Category:Candidates for speedy deletion',
            'gcmlimit': self.bigLimit,
            'prop': 'info'}
        revRemoteIdsToCheck = []
        pageIds = []
        while 1:
            gen = pywikibot.data.api.Request(
                self.siteWikipedia, parameters=myParameters )
            data = gen.submit()
            pprint.pprint(data)
            #for page in data['query']['pages'].items():
            for pageId,page in data['query']['pages'].items():
                if not page['lastrevid'] in self.revIdsAlreadyGathered:
                    revRemoteIdsToCheck.append(page['lastrevid'])
                    pageIds.append(pageId)
                #print (key)
                #print(page['lastrevid'])
            if 'query-continue' in data:
                myParameters['gcmcontinue'] = data['query-continue']['categorymembers']['gcmcontinue']
            else:
                break
        if pageIds:
            self.revIdsAlreadyGathered.extend( self.getTalkPages( pageIds ) )
        if revRemoteIdsToCheck:
            self.revIdsAlreadyGathered.extend( self.checkRevRemoteIds( revRemoteIdsToCheck ) )
            
    def getTalkPages ( self, pageIds ):
        gathered = []
        revIds = []
        myParameters = {'action': 'query',
            'prop': 'info',
            'inprop': 'talkid'}
        count = 0
        pageIdsCheckingThisTime = []
        talkIdsToGet = []
        for pageId in pageIds:
            count = count + 1
            pageIdsCheckingThisTime.append(pageId)
            # Confirmed, the limit is 50 page IDs
            if count == len(pageIds) or not count % self.smallLimit:
                print ('Page IDs:')
                print (*pageIdsCheckingThisTime)
                myParameters['pageids'] = '|'.join( pageIdsCheckingThisTime )
                gen = pywikibot.data.api.Request(
                    self.siteWikipedia, parameters=myParameters )
                data = gen.submit()
                print ( 'Data1:' )
                pprint.pprint(data)
                for subjectPageId, page in data['query']['pages'].items():
                    if 'talkid' in page:
                        talkIdsToGet.append(page['talkid'])
                pageIdsCheckingThisTime = []
        talkIdsGettingThisTime = []
        count = 0
        for talkIdToGet in talkIdsToGet:
            count = count + 1
            talkIdsGettingThisTime.append(str(talkIdToGet))
            if count == len(talkIdsToGet) or not count % self.smallLimit:
                myParameters = { 'action': 'query',
                    'prop': 'info',
                    'pageids': '|'.join(talkIdsGettingThisTime) }
                gen = pywikibot.data.api.Request(
                    self.siteWikipedia, parameters=myParameters )
                data = gen.submit()
                print ( 'Data2:' )
                pprint.pprint(data)
                for pageId, page in data['query']['pages'].items():
                    if not page['lastrevid'] in self.revIdsAlreadyGathered:
                        revIds.append( page['lastrevid'] )
        if revIds:
            gathered = self.checkRevRemoteIds( revIds )
        return gathered
    
    def getHistory( self, pageId, title ):
        myParameters = { 'action': 'query',
            'prop': 'revisions',
            'rvprop': 'ids|timestamp|user|userid|size|comment|tags',
            'pageids': pageId,
            "rvlimit": self.bigLimit }
        pageText = "{{sdwikistart}}\n"
        while 1:
            gen = pywikibot.data.api.Request(
                self.siteWikipedia, parameters=myParameters )
            data = gen.submit()
            print ( 'getHistory data1:')
            pprint.pprint(data)
            for pageId,page in data['query']['pages'].items():
                for revision in page['revisions']:
                    pageText = pageText + "{{sdwikientry|"
                    pageText = pageText + "|user=" + revision['user']
                    pageText = pageText + "|timestamp=" + revision['timestamp']
                    pageText = pageText + "|size=" + str( revision['size'] )
                    pageText = pageText + "|comment=" + revision['comment'] + "}}\n" 
                #print (key)
                #print(page['lastrevid'])
            if 'continue' in data:
                myParameters['rvcontinue'] = data['continue']['rvcontinue']
            else:
                break
        pageText = pageText + "{{sdwikiend}}\n"
        myParameters = {'action': 'edit',
            'title': 'History:' + title,
            'token': self.siteTest.tokens['edit'],
            'text': pageText}
        #pprint.pprint(myParameters)
        gen = pywikibot.data.api.Request(
            self.siteTest, parameters=myParameters )
        data = gen.submit()
        
    def checkRevRemoteIds( self, revRemoteIdsToCheck ):
        print ('To check:')
        print (*revRemoteIdsToCheck)
        revRemoteIdsCheckingThisTime = []
        revIdsToGet = []
        gathered = []
        count = 0
        for remoteRevIdToCheck in revRemoteIdsToCheck:
            count = count + 1
            revRemoteIdsCheckingThisTime.append( str(remoteRevIdToCheck) )
            if count == len(revRemoteIdsToCheck) or not count % self.bigLimit:
                print ('To check this time:')
                print (*revRemoteIdsCheckingThisTime)
                myParameters = {'action': 'revremoteid',
                    'revremoteids': '|'.join( revRemoteIdsCheckingThisTime )
                }
                pprint.pprint(myParameters)
                gen = pywikibot.data.api.Request(
                    self.siteTest, parameters=myParameters )
                data = gen.submit()
                print( 'Data3:' )
                pprint.pprint(data)
                revIdsToGet.extend( data['revremoteid']['revremoteidsnotindb'] )
                gathered.extend( data['revremoteid']['revremoteidsindb'] )
                revRemoteIdsCheckingThisTime = []
        print( 'revIdsToGet:' )
        print (*revIdsToGet)
        if revIdsToGet:
            gathered.extend( self.getRevs( revIdsToGet ) )
        return gathered
                
    def getRevs( self, revIdsToGet ):
        revsGettingThisTime = []
        gotten = []
        count = 0
        myWikipediaParameters = {'action': 'query',
            'prop': 'revisions',
            'rvprop': 'ids|flags|timestamp|user|comment|content|tags',
            'rvslots': 'main'}
        for revIdToGet in revIdsToGet:
            count = count + 1
            print ( 'Count: ' + str(count) + ' Len: ' + str(len(revIdsToGet)) )
            revsGettingThisTime.append( str(revIdToGet) )
            if count == len(revIdsToGet) or not count % self.smallLimit:
                print ( 'Revs getting this time:' )
                print (*revsGettingThisTime)
                myWikipediaParameters['revids'] = '|' . join(revsGettingThisTime)
                gen = pywikibot.data.api.Request(
                    self.siteWikipedia, parameters=myWikipediaParameters )
                data = gen.submit()
                #pprint.pprint(data)
                gotten = self.pushPages( data['query']['pages'] )
                revsGettingThisTime = []
        return gotten
    
    def pushPages ( self, pages ):
        gotten = []
        for pageid, page in pages.items():
            title = page['title']
            for revision in page['revisions']:
                print ( 'Pushing revision ' + str ( revision['revid']) )
                myParameters = {'action': 'edit',
                    'title': title,
                    'token': self.siteTest.tokens['edit'] }
                myParameters['summary']  = revision['comment']
                if 'minor' in revision:
                    myParameters['minor'] = 'true'
                if 'bot' in revision:
                    myParameters['bot'] = 'true'
                myParameters['sdtimestamp'] = revision['timestamp']
                myParameters['sduser'] = revision['user']
                #myParameters['sdcontentmodel'] = revision['contentmodel']
                #myParameters['sdcontentformat'] = revision['contentformat']
                myParameters['sdtags'] = '|'.join(revision['tags'])
                #myParameters['contentmodel'] = revision['contentmodel']
                #myParameters['contentformat'] = revision['contentformat']
                #myParameters['tags'] = '|'.join(revision['tags'])
                myParameters['sdrevremoteid'] = revision['revid']
                myParameters['sdsaverevremoteid'] = 'true'
                print ( 'pushPages parameters (minus text)')
                pprint.pprint(myParameters)
                myParameters['text'] = revision['slots']['main']['*']
                gen = pywikibot.data.api.Request(
                    self.siteTest, parameters=myParameters )
                data = gen.submit()
                pprint.pprint(data)
                if data['edit']['result'] == 'Success':
                    gotten.append( revision['revid'] )
                    self.getHistory( pageid, title )
                else:
                    sys.exit()
        return gotten
    
siteTest = pywikibot.Site(code='en', fam='test1')
if not siteTest.logged_in():
    siteTest.login()
siteWikipedia = pywikibot.Site(code='en', fam='wikipedia')
myTestScript = TestScript( siteTest, siteWikipedia )
myTestScript.getCategoryMembers ( 'Category:Candidates for speedy deletion' )