<?php

namespace Ibtikar\GlanceUMSBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ibtikar\GlanceDashboardBundle\Document\EmailTemplate;

/**
 * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
 */
class LoadEmailTemplateData implements FixtureInterface
{

    public function load(ObjectManager $manager)
    {
        $staffForgotPassword = new EmailTemplate();
        $staffForgotPassword->setName('staff forgot password');
        $staffForgotPassword->setSubject('استعادة كلمة المرور');
        $staffForgotPassword->setMessage('لإستعادة كلمة المرور نرجو زيارة الرابط <br/>
                                                        <a href="%change_password_url%">%change_password_url%</a><br/>
                                                        . ونفيدك علماً بأنه سوف تنتهي صلاحية هذه الرسالة والرابط بعد 24 ساعة من الأن<br/>');
//        $staffForgotPassword->setTemplate('
//        <table style="table-layout:fixed;" width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
//            <tr>
//                <td align="center" width="100%" valign="top" bgcolor="#ffffff">
//                    <table width="598" style="table-layout:fixed;" border="0" cellpadding="0" cellspacing="0" align="center">
//                        <tr>
//                            <td align="center" width="598" bgcolor="#ffffff" style="background:#ffffff;border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb;">
//
//                                <table style="table-layout:fixed;" width="560" border="0" cellpadding="0" cellspacing="0" align="center" class="MobileScale">
//                                    <tr>
//                                        <td align="center" width="100%">
//
//                                            <!-- Text Section -->
//                                            <table style="table-layout:fixed;" border="0" cellpadding="0" cellspacing="0" align="center">
//                                                <tr><td align="center" width="100%" height="15" style="font-size: 15;line-height: 0;border-collapse: collapse;">&nbsp;</td></tr>
//
//                                                <tr>
//                                                    <td align="center" width="100%" style="font-size: 14px; color: #868686; text-align: right; font-weight: normal; font-family: Helvetica, Arial, sans-serif; line-height: 17px;">
//                                                        لإستعادة كلمة المرور نرجو زيارة الرابط <br/>
//                                                        <a href="%change_password_url%">%change_password_url%</a><br/>
//                                                        . ونفيدك علماً بأنه سوف تنتهي صلاحية هذه الرسالة والرابط بعد 24 ساعة من الأن<br/>
//                                                    </td>
//                                                </tr>
//                                                <tr>
//                                                    <td width="100%" >
//                                                        <table style="table-layout:fixed" width="230" cellspacing="0" cellpadding="0" border="0" align="left">
//                                                            <tbody>
//                                                                <tr>
//                                                                    <td dir="rtl" align="center" width="100%" style="text-align:center;font-size:14px;padding-top: 20px;color:#444;font-weight:bold;font-family:Helvetica,Arial,sans-serif;line-height:17px">
//واخيرا تقبل منا اطيب التحيات،<br/>
//مطبخ قودي
//                                                                   </td>
//                                                                </tr>
//                                                            </tbody>
//                                                        </table>
//                                                    </td>
//                                                </tr>
//                                                <tr><td align="center" width="100%" height="15" style="font-size: 15;line-height: 0;border-collapse: collapse;">&nbsp;</td></tr>
//
//                                            </table>
//                                            <!-- End of Text Section -->
//
//                                        </td>
//                                    </tr>
//                                </table>
//
//                            </td>
//                        </tr>
//                    </table>
//                </td>
//            </tr>
//        </table>
//');
        $staffForgotPassword->setTemplate('');


        $manager->persist($staffForgotPassword);
        $manager->flush();
    }
}
