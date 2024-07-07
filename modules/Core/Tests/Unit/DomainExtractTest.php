<?php
 

namespace Modules\Core\Tests\Unit;

use Modules\Core\App\Support\Domain;
use PHPUnit\Framework\TestCase;

class DomainExtractTest extends TestCase
{
    protected $tlds = ['.aero', '.biz', '.cat', '.com', '.coop', '.edu', '.gov', '.info', '.int', '.jobs', '.mil', '.mobi', '.museum',
        '.name', '.net', '.org', '.travel', '.ac', '.ad', '.ae', '.af', '.ag', '.ai', '.al', '.am', '.an', '.ao', '.aq', '.ar', '.as', '.at', '.au', '.aw',
        '.az', '.ba', '.bb', '.bd', '.be', '.bf', '.bg', '.bh', '.bi', '.bj', '.bm', '.bn', '.bo', '.br', '.bs', '.bt', '.bv', '.bw', '.by', '.bz', '.ca',
        '.cc', '.cd', '.cf', '.cg', '.ch', '.ci', '.ck', '.cl', '.cm', '.cn', '.co', '.cr', '.cs', '.cu', '.cv', '.cx', '.cy', '.cz', '.de', '.dj', '.dk', '.dm',
        '.do', '.dz', '.ec', '.ee', '.eg', '.eh', '.er', '.es', '.et', '.eu', '.fi', '.fj', '.fk', '.fm', '.fo', '.fr', '.ga', '.gb', '.gd', '.ge', '.gf', '.gg', '.gh',
        '.gi', '.gl', '.gm', '.gn', '.gp', '.gq', '.gr', '.gs', '.gt', '.gu', '.gw', '.gy', '.hk', '.hm', '.hn', '.hr', '.ht', '.hu', '.id', '.ie', '.il', '.im',
        '.in', '.io', '.iq', '.ir', '.is', '.it', '.je', '.jm', '.jo', '.jp', '.ke', '.kg', '.kh', '.ki', '.km', '.kn', '.kp', '.kr', '.kw', '.ky', '.kz', '.la', '.lb',
        '.lc', '.li', '.lk', '.lr', '.ls', '.lt', '.lu', '.lv', '.ly', '.ma', '.mc', '.md', '.mg', '.mh', '.mk', '.ml', '.mm', '.mn', '.mo', '.mp', '.mq',
        '.mr', '.ms', '.mt', '.mu', '.mv', '.mw', '.mx', '.my', '.mz', '.na', '.nc', '.ne', '.nf', '.ng', '.ni', '.nl', '.no', '.np', '.nr', '.nu',
        '.nz', '.om', '.pa', '.pe', '.pf', '.pg', '.ph', '.pk', '.pl', '.pm', '.pn', '.pr', '.ps', '.pt', '.pw', '.py', '.qa', '.re', '.ro', '.ru', '.rw',
        '.sa', '.sb', '.sc', '.sd', '.se', '.sg', '.sh', '.si', '.sj', '.sk', '.sl', '.sm', '.sn', '.so', '.sr', '.st', '.su', '.sv', '.sy', '.sz', '.tc', '.td', '.tf',
        '.tg', '.th', '.tj', '.tk', '.tm', '.tn', '.to', '.tp', '.tr', '.tt', '.tv', '.tw', '.tz', '.ua', '.ug', '.uk', '.um', '.us', '.uy', '.uz', '.va', '.vc',
        '.ve', '.vg', '.vi', '.vn', '.vu', '.wf', '.ws', '.ye', '.yt', '.yu', '.za', '.zm', '.zr', '.zw', '.dev', '.localhost', '.example', '.test', '.invalid', '.digital', '.online', ];

    public function test_it_does_not_extract_domain_when_provided_domain_is_empty()
    {
        $this->assertEmpty(Domain::extractFromUrl(''));
    }

    public function test_domain_name_extract_works_properly()
    {
        $totalTlds = count($this->tlds);

        $totalMatchesWithSubdomain = 0;
        $totalMatchesWithSubdomainAndSubFolder = 0;
        $totalMatchesWithSubfolder = 0;

        foreach ($this->tlds as $tld) {
            if (Domain::extractFromUrl('https://crm'.$tld.'/crm') === 'crm'.$tld) {
                $totalMatchesWithSubfolder++;
            }
        }

        foreach ($this->tlds as $tld) {
            if (Domain::extractFromUrl('https://crm.crm'.$tld) === 'crm'.$tld) {
                $totalMatchesWithSubdomain++;
            }
        }

        foreach ($this->tlds as $tld) {
            if (Domain::extractFromUrl('https://app.crm'.$tld.'/crm') === 'crm'.$tld) {
                $totalMatchesWithSubdomainAndSubFolder++;
            }
        }

        $this->assertEquals($totalMatchesWithSubdomain, $totalTlds);
        $this->assertEquals($totalMatchesWithSubdomainAndSubFolder, $totalTlds);
        $this->assertEquals($totalMatchesWithSubfolder, $totalTlds);

        // Without https test
        $this->assertEquals(Domain::extractFromUrl('http://crm.crm.com'), 'crm.com');
        $this->assertEquals(Domain::extractFromUrl('crm.crm.com'), 'crm.com');
    }
}
