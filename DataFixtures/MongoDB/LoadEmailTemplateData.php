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
                                                        .. ونفيدك علماً بأنه سوف تنتهي صلاحية هذه الرسالة والرابط بعد 24 ساعة من الأن<br/>');

        $staffForgotPassword->setTemplate('');


        $manager->persist($staffForgotPassword);

        $staffAdd = new EmailTemplate();
        $staffAdd->setName('add backend user');
        $staffAdd->setSubject('أهلاً بك مطبخ قودي');
        $staffAdd->setMessage('لقد إنضممتِ اليوم الى عائلة مطبخ قودي، بنحن بدورنا نهنئك ونتمنى لك مزيداً من التوفيق.');

        $staffAdd->setTemplate('                <tr>
                                                    <td>

                                                        <!-- start of right column -->
                                                        <table  width="149" align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <tr>
                                                                    <td >
                                                                        <table width="149" align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                                            <tbody>
                                                                                <!-- title -->
                                                                                <tr>
                                                                                    <td style="padding:10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 18px; color: %color% ; text-align:right; line-height: 24px;">


اسم المستخدم

                                                                                    </td>
                                                                                </tr>
                                                                                <!-- end of title -->

                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- end of right column -->

                                                        <!-- Start of left column -->
                                                        <table width="449" align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <!-- content -->
                                                                <tr>
                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; text-align:right; line-height: 24px;">
%username%
</td>
                                                                </tr>
                                                                <!-- end of content -->

                                                            </tbody>
                                                        </table>
                                                        <!-- end of left column -->


                                                    </td>
                                                </tr>

                                                <tr >
                                                    <td bgcolor="#f8f8f8">

                                                        <!-- start of right column -->
                                                        <table width="149" align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <tr>
                                                                    <td >
                                                                        <table width="149" align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                                            <tbody>
                                                                                <!-- title -->
                                                                                <tr>
                                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 18px; color:%color%; text-align:right; line-height: 24px;">


الرقم السري

                                                                                    </td>
                                                                                </tr>
                                                                                <!-- end of title -->

                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- end of right column -->

                                                        <!-- Start of left column -->
                                                        <table width="449" align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <!-- content -->
                                                                <tr>
                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; text-align:right; line-height: 24px;">
%password%
</td>
                                                                </tr>
                                                                <!-- end of content -->

                                                            </tbody>
                                                        </table>
                                                        <!-- end of left column -->


                                                    </td>
                                                </tr>
                <tr>
                                                    <td>

                                                        <!-- start of right column -->
                                                        <table  width="149" align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <tr>
                                                                    <td >
                                                                        <table width="149" align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                                            <tbody>
                                                                                <!-- title -->
                                                                                <tr>
                                                                                    <td style="padding:10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 18px; color: %color%; text-align:right; line-height: 24px;">


رابط الدخول

                                                                                    </td>
                                                                                </tr>
                                                                                <!-- end of title -->

                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- end of right column -->

                                                        <!-- Start of left column -->
                                                        <table width="449" align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <!-- content -->
                                                                <tr>
                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; text-align:right; line-height: 24px;">
%login_url%
</td>
                                                                </tr>
                                                                <!-- end of content -->

                                                            </tbody>
                                                        </table>
                                                        <!-- end of left column -->


                                                    </td>
                                                </tr>

                                                <tr >
                                                    <td bgcolor="#f8f8f8">

                                                        <!-- start of right column -->
                                                        <table width="149" align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <tr>
                                                                    <td >
                                                                        <table width="149" align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                                            <tbody>
                                                                                <!-- title -->
                                                                                <tr>
                                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 18px; color:%color%; text-align:right; line-height: 24px;">
                                                                                        الوظيفة
                                                                                    </td>
                                                                                </tr>
                                                                                <!-- end of title -->

                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- end of right column -->

                                                        <!-- Start of left column -->
                                                        <table width="449" align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
                                                            <tbody>
                                                                <!-- content -->
                                                                <tr>
                                                                    <td style="padding: 10px 20px;font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; text-align:right; line-height: 24px;">
%job%
</td>
                                                                </tr>
                                                                <!-- end of content -->

                                                            </tbody>
                                                        </table>
                                                        <!-- end of left column -->


                                                    </td>
                                                </tr>
');


        $manager->persist($staffAdd);
        $manager->flush();
    }
}
